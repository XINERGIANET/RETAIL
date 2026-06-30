<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchElectronicBillingConfig;
use App\Models\Movement;
use App\Models\TaxRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Integración con Apisunat para emisión de comprobantes electrónicos (Boleta/Factura) ante SUNAT.
 *
 * Adaptado de XINERGIAPRO. No incluye anticipos/detracción/retención — ver plan_anticipos_sunat.md
 * para esa extensión, que se implementa en una fase posterior.
 */
class ApisunatService
{
    /** Catálogo 51 (cbc:ProfileID): venta interna estándar. */
    private const SUNAT_OPERATION_STANDARD_CODE = '0101';

    public function isEligibleDocument(Movement $sale): bool
    {
        $docName = mb_strtolower(trim((string) ($sale->documentType?->name ?? '')), 'UTF-8');

        return str_contains($docName, 'boleta') || str_contains($docName, 'factura');
    }

    public function resolveConfigForBranch(?Branch $branch): ?BranchElectronicBillingConfig
    {
        if (! $branch) {
            return null;
        }

        $branch->loadMissing('electronicBillingConfig');

        return $branch->electronicBillingConfig;
    }

    public function isConfiguredForBranch(?Branch $branch): bool
    {
        $config = $this->resolveConfigForBranch($branch);

        if (! $config || ! $config->enabled) {
            return false;
        }

        return $this->resolveApiUrl($config) !== ''
            && trim((string) $config->persona_id) !== ''
            && trim((string) $config->persona_token) !== '';
    }

    /**
     * Punto de entrada único para los controladores: emite (si corresponde) y persiste el resultado,
     * sin lanzar excepción si Apisunat falla (la venta no debe revertirse por un error de SUNAT).
     */
    public function syncForMovement(Movement $movement): array
    {
        $movement->loadMissing(['branch', 'documentType']);

        if (! $this->isEligibleDocument($movement)) {
            return ['status' => 'SKIPPED'];
        }

        if (! $this->isConfiguredForBranch($movement->branch)) {
            return ['status' => 'NOT_CONFIGURED', 'message' => 'La sucursal no tiene Apisunat configurado.'];
        }

        try {
            $result = $this->emitSale($movement);
            $this->persistEmittedElectronicData($movement, $result);

            return $result;
        } catch (\Throwable $e) {
            Log::error('ApisunatService::syncForMovement', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
            ]);

            $movement->update([
                'electronic_invoice_provider' => 'apisunat',
                'electronic_invoice_status' => 'ERROR',
                'electronic_invoice_response' => ['error' => $e->getMessage()],
            ]);

            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    public function emitSale(Movement $sale): array
    {
        $sale->loadMissing([
            'documentType',
            'person',
            'branch',
            'salesMovement.details.taxRate',
        ]);

        if (! $this->isEligibleDocument($sale)) {
            return [
                'status' => 'SKIPPED',
                'message' => 'El tipo de documento no requiere envío electrónico.',
            ];
        }

        if ($sale->electronic_invoice_external_id) {
            return [
                'status' => 'SENT',
                'message' => 'El comprobante electrónico ya fue emitido.',
                'data' => $this->movementElectronicData($sale),
            ];
        }

        $branch = $sale->branch;
        $config = $this->resolveConfigForBranch($branch);

        if (! $config || ! $config->enabled) {
            throw new \RuntimeException('La sucursal no tiene configurada la facturación electrónica.');
        }

        $catalog = $this->resolveDocumentCatalog($sale, $config);
        $customerDocument = $this->resolveCustomerDocument($sale, $catalog['type']);
        $customerDocType = $this->resolveCustomerDocumentType($customerDocument, $catalog['type']);
        $totals = $this->resolveMovementTotals($sale);
        $apiUrl = $this->resolveApiUrl($config);
        $number = $this->fetchSuggestedCorrelativeNumber($config, $catalog, $apiUrl);

        return $this->sendSaleBillToApisunat(
            $sale,
            $config,
            $catalog,
            $customerDocument,
            $customerDocType,
            $totals,
            $number,
            true,
            'Comprobante enviado correctamente a Apisunat.'
        );
    }

    /**
     * Reenvía a SUNAT. Si el correlativo local ya existe en Apisunat, usa el siguiente disponible.
     */
    public function reemitSale(Movement $sale): array
    {
        $sale->loadMissing(['documentType', 'person', 'branch', 'salesMovement.details.taxRate']);

        if (! $this->isEligibleDocument($sale)) {
            return ['status' => 'SKIPPED', 'message' => 'El tipo de documento no requiere envío electrónico.'];
        }

        $branch = $sale->branch;
        $config = $this->resolveConfigForBranch($branch);

        if (! $config || ! $config->enabled) {
            throw new \RuntimeException('La sucursal no tiene configurada la facturación electrónica.');
        }

        $catalog = $this->resolveDocumentCatalog($sale, $config);
        $apiUrl = $this->resolveApiUrl($config);

        // Si la emisión anterior nunca llegó a obtener un documentId (ERROR o nunca se intentó),
        // no hay nada que "reenviar" — se pide un correlativo nuevo, igual que en emitSale().
        $electronicStatus = strtoupper(trim((string) ($sale->electronic_invoice_status ?? '')));
        if ($electronicStatus === 'ERROR' || $electronicStatus === '') {
            $initialNumber = $this->fetchSuggestedCorrelativeNumber($config, $catalog, $apiUrl);
        } else {
            $billingCorrelative = $this->resolveBillingCorrelativeForResend($sale, $catalog['serie']);
            $catalog['serie'] = $billingCorrelative['serie'];
            $initialNumber = $billingCorrelative['number'];
        }

        $customerDocument = $this->resolveCustomerDocument($sale, $catalog['type']);
        $customerDocType = $this->resolveCustomerDocumentType($customerDocument, $catalog['type']);
        $totals = $this->resolveMovementTotals($sale);

        return $this->sendSaleBillToApisunat(
            $sale,
            $config,
            $catalog,
            $customerDocument,
            $customerDocType,
            $totals,
            $initialNumber,
            true,
            'Comprobante reenviado correctamente a Apisunat.'
        );
    }

    public function persistEmittedElectronicData(Movement $sale, array $result): void
    {
        if (($result['status'] ?? '') !== 'SENT' || empty($result['data'])) {
            return;
        }

        $data = $result['data'];

        $sale->update([
            'electronic_invoice_provider' => $data['provider'] ?? 'apisunat',
            'electronic_invoice_status' => 'SENT',
            'electronic_invoice_external_id' => $data['external_id'] ?? null,
            'electronic_invoice_series' => $data['series'] ?? null,
            'electronic_invoice_number' => $data['correlative'] ?? null,
            'electronic_invoice_file_name' => $data['file_name'] ?? null,
            'electronic_invoice_pdf_ticket_url' => $data['pdf_ticket_80mm'] ?? null,
            'electronic_invoice_pdf_a4_url' => $data['pdf_a4'] ?? null,
            'electronic_invoice_xml_url' => $data['xml_url'] ?? null,
            'electronic_invoice_cdr_url' => $data['cdr_url'] ?? null,
            'electronic_invoice_response' => $data['response'] ?? null,
        ]);

        $sale->loadMissing('salesMovement');
        if ($sale->salesMovement) {
            $sale->salesMovement->update([
                'billing_status' => 'INVOICED',
                'billing_number' => $data['correlative'] ?? $sale->salesMovement->billing_number,
                'series' => $data['series'] ?? $sale->salesMovement->series,
            ]);
        }

        $sale->refresh();
        $this->refreshElectronicDocumentUrlsFromApisunat($sale);
        $this->archiveXmlForMovement($sale);
        $this->archiveCdrForMovement($sale);
    }

    /**
     * @return array{content: string, filename: string}|null
     */
    public function resolveXmlFileForDownload(Movement $sale): ?array
    {
        if (strtoupper(trim((string) ($sale->electronic_invoice_status ?? ''))) !== 'SENT') {
            return null;
        }

        $localPath = $this->resolveStoredLocalXmlPath($sale);
        if ($localPath !== null && Storage::disk('local')->exists($localPath)) {
            $content = Storage::disk('local')->get($localPath);

            return $content === '' ? null : ['content' => $content, 'filename' => basename($localPath)];
        }

        $archivedPath = $this->archiveXmlForMovement($sale);
        if ($archivedPath !== null && Storage::disk('local')->exists($archivedPath)) {
            $content = Storage::disk('local')->get($archivedPath);

            return $content === '' ? null : ['content' => $content, 'filename' => basename($archivedPath)];
        }

        return null;
    }

    public function archiveXmlForMovement(Movement $sale): ?string
    {
        if (strtoupper(trim((string) ($sale->electronic_invoice_status ?? ''))) !== 'SENT') {
            return null;
        }

        $existingPath = $this->resolveStoredLocalXmlPath($sale);
        if ($existingPath !== null && Storage::disk('local')->exists($existingPath)) {
            return $existingPath;
        }

        $xmlUrl = $this->resolveXmlDownloadUrlForMovement($sale);
        if ($xmlUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(45)->get($xmlUrl);
            if ($response->failed()) {
                Log::warning('Descarga XML SUNAT fallida', ['movement_id' => (int) $sale->id, 'status' => $response->status()]);

                return null;
            }

            $body = (string) $response->body();
            if ($body === '') {
                return null;
            }

            $filename = $this->resolveDownloadFilenameFromUrl($xmlUrl, $this->resolveXmlFileName($sale));
            $relativePath = 'electronic-invoices/'.(int) ($sale->branch_id ?: 0).'/'.$filename;
            Storage::disk('local')->put($relativePath, $body);
            $this->persistLocalXmlPathOnMovement($sale, $relativePath);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('Error archivando XML electrónico: '.$e->getMessage(), ['movement_id' => (int) $sale->id]);

            return null;
        }
    }

    private function resolveStoredLocalXmlPath(Movement $sale): ?string
    {
        $response = $sale->electronic_invoice_response;
        if (! is_array($response)) {
            return null;
        }

        $path = trim((string) ($response['local_xml_path'] ?? ''));

        return $path !== '' ? $path : null;
    }

    private function persistLocalXmlPathOnMovement(Movement $sale, string $relativePath): void
    {
        $responsePayload = is_array($sale->electronic_invoice_response) ? $sale->electronic_invoice_response : [];
        $responsePayload['local_xml_path'] = $relativePath;
        $sale->update(['electronic_invoice_response' => $responsePayload]);
    }

    private function resolveXmlFileName(Movement $sale): string
    {
        $stored = trim((string) ($sale->electronic_invoice_file_name ?? ''));
        if ($stored !== '') {
            $xmlName = preg_replace('/\.pdf$/i', '.xml', $stored);

            return $xmlName !== '' ? $xmlName : ($stored.'.xml');
        }

        $serie = trim((string) ($sale->electronic_invoice_series ?? 'DOC'));
        $number = trim((string) ($sale->electronic_invoice_number ?? (string) $sale->id));
        $ruc = trim((string) ($sale->branch?->ruc ?? '0'));
        $docType = '01';
        $docName = mb_strtolower(trim((string) ($sale->documentType?->name ?? '')), 'UTF-8');
        if (str_contains($docName, 'boleta')) {
            $docType = '03';
        }

        return preg_replace('/\W+/', '-', $ruc.'-'.$docType.'-'.$serie.'-'.$number).'.xml';
    }

    /**
     * @return array{content: string, filename: string}|null
     */
    public function resolveCdrFileForDownload(Movement $sale): ?array
    {
        if (strtoupper(trim((string) ($sale->electronic_invoice_status ?? ''))) !== 'SENT') {
            return null;
        }

        $localPath = $this->resolveStoredLocalCdrPath($sale);
        if ($localPath !== null && Storage::disk('local')->exists($localPath)) {
            $content = Storage::disk('local')->get($localPath);

            return $content === '' ? null : ['content' => $content, 'filename' => basename($localPath)];
        }

        $archivedPath = $this->archiveCdrForMovement($sale);
        if ($archivedPath !== null && Storage::disk('local')->exists($archivedPath)) {
            $content = Storage::disk('local')->get($archivedPath);

            return $content === '' ? null : ['content' => $content, 'filename' => basename($archivedPath)];
        }

        return null;
    }

    public function archiveCdrForMovement(Movement $sale): ?string
    {
        if (strtoupper(trim((string) ($sale->electronic_invoice_status ?? ''))) !== 'SENT') {
            return null;
        }

        $existingPath = $this->resolveStoredLocalCdrPath($sale);
        if ($existingPath !== null && Storage::disk('local')->exists($existingPath)) {
            return $existingPath;
        }

        $cdrUrl = $this->resolveCdrDownloadUrlForMovement($sale);
        if ($cdrUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(45)->get($cdrUrl);
            if ($response->failed()) {
                Log::warning('Descarga CDR SUNAT fallida', ['movement_id' => (int) $sale->id, 'status' => $response->status()]);

                return null;
            }

            $body = (string) $response->body();
            if ($body === '') {
                return null;
            }

            $filename = $this->resolveDownloadFilenameFromUrl($cdrUrl, $this->resolveCdrFileName($sale));
            $relativePath = 'electronic-invoices/'.(int) ($sale->branch_id ?: 0).'/cdr/'.$filename;
            Storage::disk('local')->put($relativePath, $body);
            $this->persistLocalCdrPathOnMovement($sale, $relativePath);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('Error archivando CDR electrónico: '.$e->getMessage(), ['movement_id' => (int) $sale->id]);

            return null;
        }
    }

    private function resolveStoredLocalCdrPath(Movement $sale): ?string
    {
        $response = $sale->electronic_invoice_response;
        if (! is_array($response)) {
            return null;
        }

        $path = trim((string) ($response['local_cdr_path'] ?? ''));

        return $path !== '' ? $path : null;
    }

    private function persistLocalCdrPathOnMovement(Movement $sale, string $relativePath): void
    {
        $responsePayload = is_array($sale->electronic_invoice_response) ? $sale->electronic_invoice_response : [];
        $responsePayload['local_cdr_path'] = $relativePath;
        $sale->update(['electronic_invoice_response' => $responsePayload]);
    }

    private function resolveCdrFileName(Movement $sale): string
    {
        return 'R-'.$this->resolveApisunatFileNameBase($sale).'.zip';
    }

    /**
     * @return array{serie: string, number: string}
     */
    private function resolveBillingCorrelativeForResend(Movement $sale, string $defaultSerie): array
    {
        $serie = trim((string) ($sale->salesMovement?->series ?? $sale->electronic_invoice_series ?? ''));
        if ($serie === '' || $serie === '001') {
            $serie = trim($defaultSerie);
        }

        $billingRaw = trim((string) ($sale->salesMovement?->billing_number ?? $sale->electronic_invoice_number ?? ''));
        $digits = preg_replace('/\D+/', '', $billingRaw) ?: '';
        if ($digits === '') {
            throw new \RuntimeException('La venta no tiene serie/correlativo registrado para reenviar el comprobante electrónico.');
        }

        return ['serie' => $serie, 'number' => str_pad($digits, 8, '0', STR_PAD_LEFT)];
    }

    private function sendSaleBillToApisunat(
        Movement $sale,
        BranchElectronicBillingConfig $config,
        array $catalog,
        string $customerDocument,
        string $customerDocType,
        array $totals,
        string $initialNumber,
        bool $retryOnDuplicate,
        string $successMessage
    ): array {
        $branch = $sale->branch;
        $apiUrl = $this->resolveApiUrl($config);
        $originalNumber = $this->normalizeCorrelativeNumber($initialNumber);
        $number = $originalNumber;
        $maxAttempts = $retryOnDuplicate ? 6 : 1;
        $lastError = '';

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $fileName = trim((string) ($branch?->ruc ?? '0')).'-'.$catalog['type'].'-'.$catalog['serie'].'-'.$number;
            $documentBody = $this->buildDocumentBody($sale, $catalog, $customerDocument, $customerDocType, $totals, $number);
            $this->validateDocumentBodyForSunat($documentBody);

            $sendResp = Http::timeout(35)->post($apiUrl.'/personas/v1/sendBill', [
                'personaId' => (string) $config->persona_id,
                'personaToken' => (string) $config->persona_token,
                'fileName' => $fileName,
                'documentBody' => $documentBody,
            ]);

            if (! $sendResp->failed()) {
                $result = $sendResp->object();
                $documentId = trim((string) data_get($result, 'documentId', ''));
                if ($documentId === '') {
                    throw new \RuntimeException('Apisunat no devolvió documentId.');
                }

                $extraDocumentData = $this->getDocumentById($documentId, $branch);
                $urls = $this->extractDocumentUrls($extraDocumentData);
                $previousCorrelative = $originalNumber !== $number ? $originalNumber : null;

                if ($previousCorrelative !== null) {
                    $successMessage .= ' Se actualizó el correlativo interno de '
                        .$catalog['serie'].'-'.$previousCorrelative.' a '.$catalog['serie'].'-'.$number.'.';
                }

                return $this->buildEmitSuccessPayload(
                    $catalog, $number, $fileName, $documentId, $sendResp, $extraDocumentData, $urls, $apiUrl,
                    $successMessage, $previousCorrelative
                );
            }

            $lastError = (string) (data_get($sendResp->object(), 'error.message')
                ?: data_get($sendResp->json(), 'error.message')
                ?: $sendResp->body());

            if (! $retryOnDuplicate || ! $this->isDuplicateCorrelativeError($lastError)) {
                break;
            }

            $nextNumber = $this->fetchSuggestedCorrelativeNumber($config, $catalog, $apiUrl);
            if ($nextNumber === $number) {
                $nextNumber = $this->bumpCorrelativeNumber($number);
            }
            $number = $nextNumber;
        }

        $actionLabel = str_contains(mb_strtolower($successMessage, 'UTF-8'), 'reenvi') ? 'reenviando' : 'enviando';

        throw new \RuntimeException('Error '.$actionLabel.' comprobante a Apisunat: '.$lastError);
    }

    private function fetchSuggestedCorrelativeNumber(BranchElectronicBillingConfig $config, array $catalog, string $apiUrl): string
    {
        $correlativeResp = Http::timeout(20)->post($apiUrl.'/personas/lastDocument', [
            'personaId' => (string) $config->persona_id,
            'personaToken' => (string) $config->persona_token,
            'type' => $catalog['type'],
            'serie' => $catalog['serie'],
        ]);

        if ($correlativeResp->failed()) {
            throw new \RuntimeException('Error consultando correlativo en Apisunat: '.$correlativeResp->body());
        }

        $suggestedNumber = trim((string) data_get($correlativeResp->object(), 'suggestedNumber', ''));
        if ($suggestedNumber === '' || ! ctype_digit($suggestedNumber)) {
            throw new \RuntimeException('Apisunat devolvió un correlativo inválido.');
        }

        return $this->normalizeCorrelativeNumber($suggestedNumber);
    }

    private function normalizeCorrelativeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?: '';

        return str_pad($digits !== '' ? $digits : '1', 8, '0', STR_PAD_LEFT);
    }

    private function bumpCorrelativeNumber(string $number): string
    {
        $digits = (int) (preg_replace('/\D+/', '', $number) ?: '0');

        return str_pad((string) ($digits + 1), 8, '0', STR_PAD_LEFT);
    }

    private function isDuplicateCorrelativeError(string $message): bool
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        return str_contains($normalized, 'numeración repetida')
            || str_contains($normalized, 'numeracion repetida')
            || str_contains($normalized, 'número repetido')
            || str_contains($normalized, 'numero repetido')
            || str_contains($normalized, 'ya existe')
            || str_contains($normalized, 'repetid');
    }

    private function buildEmitSuccessPayload(
        array $catalog,
        string $number,
        string $fileName,
        string $documentId,
        \Illuminate\Http\Client\Response $sendResp,
        array $extraDocumentData,
        array $urls,
        string $apiUrl,
        string $message = 'Comprobante enviado correctamente a Apisunat.',
        ?string $previousCorrelative = null
    ): array {
        $data = [
            'provider' => 'apisunat',
            'external_id' => $documentId,
            'series' => $catalog['serie'],
            'correlative' => $number,
            'full_number' => $catalog['serie'].'-'.$number,
            'file_name' => $fileName.'.pdf',
            'pdf_ticket_80mm' => $apiUrl.'/documents/'.$documentId.'/getPDF/ticket80mm/'.$fileName.'.pdf',
            'pdf_a4' => $apiUrl.'/documents/'.$documentId.'/getPDF/A4/'.$fileName.'.pdf',
            'xml_url' => $urls['xml_url'] ?? $this->buildApisunatXmlDownloadUrl($apiUrl, $documentId, $fileName),
            'cdr_url' => $urls['cdr_url'] ?? $this->buildApisunatCdrDownloadUrl($apiUrl, $documentId, $fileName),
            'response' => ['send' => $sendResp->json(), 'document' => $extraDocumentData],
        ];

        if ($previousCorrelative !== null) {
            $data['previous_correlative'] = $previousCorrelative;
            $data['correlative_changed'] = true;
        }

        return ['status' => 'SENT', 'message' => $message, 'data' => $data];
    }

    public function consultDocument(?Branch $branch, string $document): array
    {
        $document = trim($document);
        $config = $this->resolveConfigForBranch($branch);

        if (! $config || ! $config->enabled) {
            throw new \RuntimeException('La sucursal no tiene configurada la consulta documental.');
        }

        $apiUrl = $this->resolveApiUrl($config);
        if (strlen($document) === 8) {
            $url = $apiUrl.'/personas/'.trim((string) $config->persona_id).'/getDNI?dni='.$document.'&personaToken='.rawurlencode((string) $config->persona_token);
        } elseif (strlen($document) === 11) {
            $url = $apiUrl.'/personas/'.trim((string) $config->persona_id).'/getRUC?ruc='.$document.'&personaToken='.rawurlencode((string) $config->persona_token);
        } else {
            throw new \RuntimeException('Documento inválido.');
        }

        $response = Http::timeout(20)->get($url);
        if ($response->failed()) {
            throw new \RuntimeException('No se pudo consultar el documento.');
        }

        return (array) ($response->json('data') ?? []);
    }

    public function getDocumentById(string $documentId, ?Branch $branch = null): array
    {
        $apiUrl = $this->resolveApiUrl($this->resolveConfigForBranch($branch));
        $response = Http::timeout(20)->get($apiUrl.'/documents/'.$documentId.'/getById');

        if ($response->failed()) {
            throw new \RuntimeException('No se pudo consultar el comprobante electrónico.');
        }

        return $response->json() ?? [];
    }

    public function extractDocumentUrls(array $payload): array
    {
        $xmlUrl = $this->extractApisunatGetByIdUrl($payload, 'xml') ?? $this->findXmlUrl($payload);
        $cdrUrl = $this->extractApisunatGetByIdUrl($payload, 'cdr') ?? $this->findCdrUrl($payload);

        return [
            'xml_url' => $xmlUrl,
            'cdr_url' => $cdrUrl,
            'pdf_a4_url' => $this->findUrlByKeyword($payload, ['pdf', 'a4']),
            'pdf_ticket_url' => $this->findUrlByKeyword($payload, ['pdf', 'ticket']),
            'sunat_status' => trim((string) data_get($payload, 'status', data_get($payload, 'data.status', ''))),
            'file_name' => trim((string) data_get($payload, 'fileName', data_get($payload, 'data.fileName', ''))),
        ];
    }

    /**
     * @return array{xml_url: ?string, cdr_url: ?string, sunat_status: ?string, file_name: ?string}
     */
    public function refreshElectronicDocumentUrlsFromApisunat(Movement $sale): array
    {
        $documentId = trim((string) ($sale->electronic_invoice_external_id ?? ''));
        if ($documentId === '') {
            return ['xml_url' => null, 'cdr_url' => null, 'sunat_status' => null, 'file_name' => null];
        }

        $sale->loadMissing('branch');

        try {
            $documentData = $this->getDocumentById($documentId, $sale->branch);
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar getById en Apisunat: '.$e->getMessage(), [
                'movement_id' => (int) $sale->id,
                'document_id' => $documentId,
            ]);

            return ['xml_url' => null, 'cdr_url' => null, 'sunat_status' => null, 'file_name' => null];
        }

        $urls = $this->extractDocumentUrls($documentData);
        $xmlUrl = trim((string) ($urls['xml_url'] ?? ''));
        $cdrUrl = trim((string) ($urls['cdr_url'] ?? ''));
        $sunatStatus = trim((string) ($urls['sunat_status'] ?? ''));
        $fileName = trim((string) ($urls['file_name'] ?? ''));

        $update = [];
        if ($xmlUrl !== '') {
            $update['electronic_invoice_xml_url'] = $xmlUrl;
        }
        if ($cdrUrl !== '') {
            $update['electronic_invoice_cdr_url'] = $cdrUrl;
        }
        if ($fileName !== '' && trim((string) ($sale->electronic_invoice_file_name ?? '')) === '') {
            $update['electronic_invoice_file_name'] = str_ends_with(strtolower($fileName), '.pdf') ? $fileName : $fileName.'.pdf';
        }

        if ($update !== [] || $sunatStatus !== '') {
            $responsePayload = is_array($sale->electronic_invoice_response) ? $sale->electronic_invoice_response : [];
            if ($sunatStatus !== '') {
                $responsePayload['apisunat_status'] = $sunatStatus;
            }
            $update['electronic_invoice_response'] = $responsePayload;
            $sale->update($update);
            $sale->refresh();
        }

        return [
            'xml_url' => $xmlUrl !== '' ? $xmlUrl : null,
            'cdr_url' => $cdrUrl !== '' ? $cdrUrl : null,
            'sunat_status' => $sunatStatus !== '' ? $sunatStatus : null,
            'file_name' => $fileName !== '' ? $fileName : null,
        ];
    }

    private function resolveXmlDownloadUrlForMovement(Movement $sale): string
    {
        $stored = trim((string) ($sale->electronic_invoice_xml_url ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $refreshed = $this->refreshElectronicDocumentUrlsFromApisunat($sale);

        return trim((string) ($refreshed['xml_url'] ?? ''));
    }

    private function resolveCdrDownloadUrlForMovement(Movement $sale): string
    {
        $stored = trim((string) ($sale->electronic_invoice_cdr_url ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $refreshed = $this->refreshElectronicDocumentUrlsFromApisunat($sale);
        $cdrUrl = trim((string) ($refreshed['cdr_url'] ?? ''));
        if ($cdrUrl !== '') {
            return $cdrUrl;
        }

        return trim((string) ($this->resolveApisunatCdrDownloadUrlFallback($sale) ?? ''));
    }

    public function buildApisunatCdrDownloadUrl(string $apiUrl, string $documentId, string $fileNameBase): string
    {
        return rtrim($apiUrl, '/').'/documents/'.trim($documentId).'/getCDR/R-'.trim($fileNameBase).'.zip';
    }

    public function buildApisunatXmlDownloadUrl(string $apiUrl, string $documentId, string $fileNameBase): string
    {
        return rtrim($apiUrl, '/').'/documents/'.trim($documentId).'/getXML/'.trim($fileNameBase).'.xml';
    }

    public function resolveApisunatCdrDownloadUrl(Movement $sale): ?string
    {
        $url = $this->resolveCdrDownloadUrlForMovement($sale);

        return $url !== '' ? $url : null;
    }

    private function resolveApisunatCdrDownloadUrlFallback(Movement $sale): ?string
    {
        $documentId = trim((string) ($sale->electronic_invoice_external_id ?? ''));
        if ($documentId === '') {
            return null;
        }

        $config = $this->resolveConfigForBranch($sale->branch);
        if (! $config) {
            return null;
        }

        return $this->buildApisunatCdrDownloadUrl($this->resolveApiUrl($config), $documentId, $this->resolveApisunatFileNameBase($sale));
    }

    private function extractApisunatGetByIdUrl(array $payload, string $field): ?string
    {
        $candidates = [data_get($payload, $field), data_get($payload, 'data.'.$field), data_get($payload, 'document.'.$field)];

        foreach ($candidates as $url) {
            $url = trim((string) $url);
            if ($url !== '' && Str::startsWith($url, ['http://', 'https://'])) {
                return $url;
            }
        }

        return null;
    }

    private function resolveDownloadFilenameFromUrl(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $basename = is_string($path) ? trim(basename($path)) : '';

        return $basename !== '' ? $basename : $fallback;
    }

    private function resolveApisunatFileNameBase(Movement $sale): string
    {
        $stored = trim((string) ($sale->electronic_invoice_file_name ?? ''));
        if ($stored !== '') {
            return preg_replace('/\.(pdf|xml|zip)$/i', '', $stored) ?: $stored;
        }

        $ruc = trim((string) ($sale->branch?->ruc ?? '0'));
        $serie = trim((string) ($sale->electronic_invoice_series ?? $sale->salesMovement?->series ?? 'DOC'));
        $number = trim((string) ($sale->electronic_invoice_number ?? $sale->salesMovement?->billing_number ?? (string) $sale->id));
        $docType = '01';
        $docName = mb_strtolower(trim((string) ($sale->documentType?->name ?? '')), 'UTF-8');
        if (str_contains($docName, 'boleta')) {
            $docType = '03';
        }

        return preg_replace('/\W+/', '-', $ruc.'-'.$docType.'-'.$serie.'-'.$number);
    }

    private function findXmlUrl(array $payload): ?string
    {
        $url = $this->findUrlByKeyword($payload, ['getxml']);
        if ($url) {
            return $url;
        }

        $url = $this->findUrlByKeyword($payload, ['xml']);
        if ($url && ! str_contains(strtolower($url), 'cdr')) {
            return $url;
        }

        return null;
    }

    private function findCdrUrl(array $payload): ?string
    {
        foreach (['getcdr', 'cdr', 'constancia'] as $keyword) {
            $url = $this->findUrlByKeyword($payload, [$keyword]);
            if ($url) {
                return $url;
            }
        }

        $urls = [];
        array_walk_recursive($payload, function ($value) use (&$urls) {
            if (is_string($value) && Str::startsWith($value, ['http://', 'https://'])) {
                $urls[] = $value;
            }
        });

        foreach ($urls as $candidate) {
            $normalized = Str::lower($candidate);
            if (str_contains($normalized, '/getcdr/') || (str_contains($normalized, 'r-') && str_contains($normalized, '.zip'))) {
                return $candidate;
            }
        }

        return null;
    }

    private function movementElectronicData(Movement $sale): array
    {
        return [
            'provider' => $sale->electronic_invoice_provider,
            'external_id' => $sale->electronic_invoice_external_id,
            'series' => $sale->electronic_invoice_series,
            'full_number' => $sale->electronic_invoice_number,
            'file_name' => $sale->electronic_invoice_file_name,
            'pdf_ticket_80mm' => $sale->electronic_invoice_pdf_ticket_url,
            'pdf_a4' => $sale->electronic_invoice_pdf_a4_url,
            'xml_url' => $sale->electronic_invoice_xml_url,
            'cdr_url' => $sale->electronic_invoice_cdr_url,
            'response' => $sale->electronic_invoice_response,
        ];
    }

    private function resolveApiUrl(?BranchElectronicBillingConfig $config): string
    {
        return rtrim(trim((string) ($config?->api_url ?: config('apisunat.url'))), '/');
    }

    private function resolveDocumentCatalog(Movement $sale, BranchElectronicBillingConfig $config): array
    {
        $docName = mb_strtolower(trim((string) ($sale->documentType?->name ?? '')), 'UTF-8');

        if (str_contains($docName, 'factura')) {
            return [
                'type' => '01',
                'serie' => trim((string) ($config->series_factura ?: config('apisunat.series.factura', 'F001'))),
            ];
        }

        if (str_contains($docName, 'boleta')) {
            return [
                'type' => '03',
                'serie' => trim((string) ($config->series_boleta ?: config('apisunat.series.boleta', 'B001'))),
            ];
        }

        throw new \RuntimeException('Solo boleta y factura pueden enviarse a Apisunat.');
    }

    private function resolveCustomerDocument(Movement $sale, string $documentTypeCode): string
    {
        $document = preg_replace('/\D+/', '', (string) ($sale->person?->document_number ?? '')) ?: '';

        if ($documentTypeCode === '01') {
            if (strlen($document) !== 11) {
                throw new \RuntimeException('La factura requiere un cliente con RUC válido.');
            }

            return $document;
        }

        return $document !== '' ? $document : '0';
    }

    private function resolveCustomerDocumentType(string $document, string $documentTypeCode): string
    {
        if ($documentTypeCode === '01') {
            return '6';
        }

        if (strlen($document) === 11) {
            return '6';
        }
        if (strlen($document) === 8) {
            return '1';
        }

        return '0';
    }

    private function resolveMovementTotals(Movement $sale): array
    {
        $subtotal = round((float) ($sale->salesMovement?->subtotal ?? 0), 2);
        $tax = round((float) ($sale->salesMovement?->tax ?? 0), 2);
        $total = round((float) ($sale->salesMovement?->total ?? 0), 2);

        return compact('subtotal', 'tax', 'total');
    }

    private function buildDocumentBody(Movement $sale, array $catalog, string $customerDocument, string $customerDocType, array $totals, string $number): array
    {
        $branch = $sale->branch;
        $customerName = trim((string) ($sale->person_name ?: 'CLIENTES VARIOS'));
        $details = $this->resolveDetailsForSale($sale);
        $defaultTaxPercent = $this->resolveDefaultTaxPercentForBranch($branch);

        $documentBody = [
            'cbc:UBLVersionID' => ['_text' => '2.1'],
            'cbc:CustomizationID' => ['_text' => '2.0'],
            'cbc:ProfileID' => self::SUNAT_OPERATION_STANDARD_CODE,
            'cbc:ID' => ['_text' => $catalog['serie'].'-'.$number],
            'cbc:IssueDate' => ['_text' => now()->format('Y-m-d')],
            'cbc:IssueTime' => ['_text' => now()->format('H:i:s')],
            'cbc:InvoiceTypeCode' => [
                '_text' => $catalog['type'],
                '_attributes' => ['listID' => self::SUNAT_OPERATION_STANDARD_CODE],
            ],
            'cbc:Note' => [],
            'cbc:DocumentCurrencyCode' => ['_text' => 'PEN'],
            'cac:AccountingSupplierParty' => [
                'cac:Party' => [
                    'cac:PartyIdentification' => [
                        'cbc:ID' => ['_attributes' => ['schemeID' => '6'], '_text' => trim((string) ($branch?->ruc ?? '0'))],
                    ],
                    'cac:PartyLegalEntity' => [
                        'cbc:RegistrationName' => ['_text' => trim((string) ($branch?->legal_name ?? config('app.name')))],
                        'cac:RegistrationAddress' => [
                            'cbc:AddressTypeCode' => ['_text' => '0000'],
                            'cac:AddressLine' => ['cbc:Line' => ['_text' => trim((string) ($branch?->address ?? '-'))]],
                        ],
                    ],
                ],
            ],
            'cac:AccountingCustomerParty' => [
                'cac:Party' => [
                    'cac:PartyIdentification' => [
                        'cbc:ID' => ['_attributes' => ['schemeID' => $customerDocType], '_text' => $customerDocument],
                    ],
                    'cac:PartyLegalEntity' => [
                        'cbc:RegistrationName' => ['_text' => $customerName !== '' ? $customerName : 'CLIENTES VARIOS'],
                    ],
                ],
            ],
            'cac:InvoiceLine' => [],
        ];

        $lineIndex = 1;
        $headerSubtotal = 0.0;
        $headerTax = 0.0;
        $headerTotal = 0.0;
        foreach ($details as $detail) {
            $qty = (float) ($detail->quantity ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $lineTotal = round((float) ($detail->amount ?? 0), 2);
            if ($lineTotal <= 0) {
                continue;
            }

            $taxFactor = $defaultTaxPercent > 0 ? ($defaultTaxPercent / 100) : 0.18;
            $lineSubtotal = round($taxFactor > 0 ? ($lineTotal / (1 + $taxFactor)) : $lineTotal, 2);
            $lineIgv = round($lineTotal - $lineSubtotal, 2);
            $grossUnitPrice = round($lineTotal / $qty, 2);
            $unitValue = round($lineSubtotal / $qty, 2);

            $description = trim((string) ($detail->description ?? 'Producto'));
            $complements = collect($detail->complements ?? [])
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->map(fn ($value) => trim((string) $value))
                ->values();
            if ($complements->isNotEmpty()) {
                $description .= ' - '.implode(', ', $complements->all());
            }

            $documentBody['cac:InvoiceLine'][] = [
                'cbc:ID' => ['_text' => $lineIndex],
                'cbc:InvoicedQuantity' => ['_attributes' => ['unitCode' => 'NIU'], '_text' => $qty],
                'cbc:LineExtensionAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $lineSubtotal],
                'cac:PricingReference' => [
                    'cac:AlternativeConditionPrice' => [
                        'cbc:PriceAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $grossUnitPrice],
                        'cbc:PriceTypeCode' => ['_text' => '01'],
                    ],
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $lineIgv],
                    'cac:TaxSubtotal' => [[
                        'cbc:TaxableAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $lineSubtotal],
                        'cbc:TaxAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $lineIgv],
                        'cac:TaxCategory' => [
                            'cbc:Percent' => ['_text' => round($taxFactor * 100, 2)],
                            'cbc:TaxExemptionReasonCode' => ['_text' => '10'],
                            'cac:TaxScheme' => [
                                'cbc:ID' => ['_text' => '1000'],
                                'cbc:Name' => ['_text' => 'IGV'],
                                'cbc:TaxTypeCode' => ['_text' => 'VAT'],
                            ],
                        ],
                    ]],
                ],
                'cac:Item' => ['cbc:Description' => ['_text' => $description]],
                'cac:Price' => ['cbc:PriceAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $unitValue]],
            ];

            $headerSubtotal += $lineSubtotal;
            $headerTax += $lineIgv;
            $headerTotal += $lineTotal;
            $lineIndex++;
        }

        $headerSubtotal = round($headerSubtotal, 2);
        $headerTax = round($headerTax, 2);
        $headerTotal = round($headerTotal, 2);

        if ($headerTotal <= 0) {
            $headerSubtotal = round((float) ($totals['subtotal'] ?? 0), 2);
            $headerTax = round((float) ($totals['tax'] ?? 0), 2);
            $headerTotal = round((float) ($totals['total'] ?? 0), 2);
        }

        $documentBody['cac:TaxTotal'] = [
            'cbc:TaxAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerTax],
            'cac:TaxSubtotal' => [
                'cbc:TaxableAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerSubtotal],
                'cbc:TaxAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerTax],
                'cac:TaxCategory' => [
                    'cac:TaxScheme' => ['cbc:ID' => ['_text' => '1000'], 'cbc:Name' => ['_text' => 'IGV'], 'cbc:TaxTypeCode' => ['_text' => 'VAT']],
                ],
            ],
        ];

        $documentBody['cac:LegalMonetaryTotal'] = [
            'cbc:LineExtensionAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerSubtotal],
            'cbc:TaxInclusiveAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerTotal],
            'cbc:PayableAmount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $headerTotal],
        ];

        if ($catalog['type'] === '01') {
            $documentBody['cac:PaymentTerms'] = $this->buildSunatInvoicePaymentTerms($this->isCreditSalesMovement($sale), $headerTotal);
        }

        return $documentBody;
    }

    private function isCreditSalesMovement(Movement $sale): bool
    {
        $paymentType = strtoupper(trim((string) ($sale->salesMovement?->payment_type ?? '')));

        return in_array($paymentType, ['CREDITO', 'CREDIT', 'DEUDA'], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSunatInvoicePaymentTerms(bool $isCreditSale, float $payableAmount): array
    {
        $amount = round(max(0, $payableAmount), 2);

        if ($isCreditSale) {
            return [
                [
                    'cbc:ID' => ['_text' => 'FormaPago'],
                    'cbc:PaymentMeansID' => ['_text' => 'Credito'],
                    'cbc:Amount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $amount],
                ],
                [
                    'cbc:ID' => ['_text' => 'FormaPago'],
                    'cbc:PaymentMeansID' => ['_text' => 'Cuota001'],
                    'cbc:Amount' => ['_attributes' => ['currencyID' => 'PEN'], '_text' => $amount],
                    'cbc:PaymentDueDate' => ['_text' => now()->format('Y-m-d')],
                ],
            ];
        }

        return [[
            'cbc:ID' => ['_text' => 'FormaPago'],
            'cbc:PaymentMeansID' => ['_text' => 'Contado'],
        ]];
    }

    private function validateDocumentBodyForSunat(array $documentBody): void
    {
        $lines = data_get($documentBody, 'cac:InvoiceLine', []);
        if (! is_array($lines) || count($lines) === 0) {
            throw new \RuntimeException('No se puede emitir electrónicamente: el comprobante no tiene líneas válidas para SUNAT.');
        }

        foreach ($lines as $idx => $line) {
            $lineNumber = $idx + 1;
            $taxSchemeId = trim((string) data_get($line, 'cac:TaxTotal.cac:TaxSubtotal.0.cac:TaxCategory.cac:TaxScheme.cbc:ID._text', ''));
            $taxSchemeName = trim((string) data_get($line, 'cac:TaxTotal.cac:TaxSubtotal.0.cac:TaxCategory.cac:TaxScheme.cbc:Name._text', ''));
            $taxTypeCode = trim((string) data_get($line, 'cac:TaxTotal.cac:TaxSubtotal.0.cac:TaxCategory.cac:TaxScheme.cbc:TaxTypeCode._text', ''));
            $taxReasonCode = trim((string) data_get($line, 'cac:TaxTotal.cac:TaxSubtotal.0.cac:TaxCategory.cbc:TaxExemptionReasonCode._text', ''));
            $taxPercentRaw = data_get($line, 'cac:TaxTotal.cac:TaxSubtotal.0.cac:TaxCategory.cbc:Percent._text');
            $taxAmountRaw = data_get($line, 'cac:TaxTotal.cbc:TaxAmount._text');
            $grossUnitPriceRaw = data_get($line, 'cac:PricingReference.cac:AlternativeConditionPrice.cbc:PriceAmount._text');
            $lineSubtotalRaw = data_get($line, 'cbc:LineExtensionAmount._text');

            if ($taxSchemeId === '' || $taxSchemeName === '' || $taxTypeCode === '' || $taxReasonCode === '') {
                throw new \RuntimeException('No se puede emitir electrónicamente: el item '.$lineNumber.' no tiene tributo IGV válido. Verifique configuración tributaria del producto.');
            }

            if (! is_numeric((string) $taxPercentRaw) || ! is_numeric((string) $taxAmountRaw)) {
                throw new \RuntimeException('No se puede emitir electrónicamente: el item '.$lineNumber.' tiene datos tributarios inválidos (porcentaje/monto IGV).');
            }

            if (! is_numeric((string) $grossUnitPriceRaw) || (float) $grossUnitPriceRaw <= 0 || ! is_numeric((string) $lineSubtotalRaw) || (float) $lineSubtotalRaw <= 0) {
                throw new \RuntimeException('No se puede emitir electrónicamente: el item '.$lineNumber.' tiene importe cero o inválido para SUNAT.');
            }
        }

        $headerTaxSchemeId = trim((string) data_get($documentBody, 'cac:TaxTotal.cac:TaxSubtotal.cac:TaxCategory.cac:TaxScheme.cbc:ID._text', ''));
        if ($headerTaxSchemeId === '') {
            throw new \RuntimeException('No se puede emitir electrónicamente: el resumen tributario del comprobante está incompleto.');
        }

        $invoiceTypeListId = trim((string) data_get($documentBody, 'cbc:InvoiceTypeCode._attributes.listID', ''));
        if ($invoiceTypeListId === '') {
            throw new \RuntimeException('No se puede emitir electrónicamente: falta listID en InvoiceTypeCode (requerido por Apisunat).');
        }

        $linesSubtotal = $this->sumInvoiceLineExtensionAmounts($documentBody);
        $headerTaxable = round((float) data_get($documentBody, 'cac:TaxTotal.cac:TaxSubtotal.cbc:TaxableAmount._text', 0), 2);

        if ($linesSubtotal > 0 && $headerTaxable > 0 && abs($linesSubtotal - $headerTaxable) > 0.009) {
            throw new \RuntimeException(
                'No se puede emitir electrónicamente: la suma de bases gravadas por línea (S/ '
                .number_format($linesSubtotal, 2, '.', '')
                .') no coincide con el total gravado del comprobante (S/ '
                .number_format($headerTaxable, 2, '.', '')
                .'). SUNAT rechazaría el comprobante (error 3277).'
            );
        }
    }

    private function sumInvoiceLineExtensionAmounts(array $documentBody): float
    {
        $lines = data_get($documentBody, 'cac:InvoiceLine', []);
        if (! is_array($lines)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($lines as $line) {
            $total += (float) data_get($line, 'cbc:LineExtensionAmount._text', 0);
        }

        return round($total, 2);
    }

    private function resolveDetailsForSale(Movement $sale): Collection
    {
        if ($sale->salesMovement) {
            return $sale->salesMovement->details->where('status', '!=', 'C')->values();
        }

        return collect();
    }

    private function resolveDefaultTaxPercentForBranch(?Branch $branch): float
    {
        $taxRateId = null;

        if ($branch?->id) {
            $taxRateId = DB::table('branch_parameters')
                ->join('parameters as p', 'p.id', '=', 'branch_parameters.parameter_id')
                ->where('branch_parameters.branch_id', $branch->id)
                ->whereRaw('LOWER(p.description) = ?', ['igv_defecto'])
                ->whereNull('branch_parameters.deleted_at')
                ->value('branch_parameters.value');
        }

        $taxRate = $taxRateId ? TaxRate::query()->whereKey((int) $taxRateId)->first() : null;

        if (! $taxRate) {
            $taxRate = TaxRate::query()->where('status', true)->orderBy('order_num')->first();
        }

        return $taxRate ? (float) $taxRate->tax_rate : 18.0;
    }

    private function findUrlByKeyword(array $payload, array $keywords): ?string
    {
        $urls = [];
        array_walk_recursive($payload, function ($value) use (&$urls) {
            if (is_string($value) && Str::startsWith($value, ['http://', 'https://'])) {
                $urls[] = $value;
            }
        });

        foreach ($urls as $url) {
            $normalized = Str::lower($url);
            $matched = true;
            foreach ($keywords as $keyword) {
                if (! str_contains($normalized, Str::lower($keyword))) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                return $url;
            }
        }

        return null;
    }
}
