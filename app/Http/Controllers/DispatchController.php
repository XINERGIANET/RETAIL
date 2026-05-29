<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Card;
use App\Models\CashMovements;
use App\Models\CashRegister;
use App\Models\CashShiftRelation;
use App\Models\DigitalWallet;
use App\Models\DocumentType;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\PaymentConcept;
use App\Models\PaymentGateways;
use App\Models\PaymentMethod;
use App\Models\SaleOrder;
use App\Models\SaleOrderPayment;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use App\Models\Shift;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $branchId = (int) session('branch_id');
        $search   = $request->input('search');
        $status   = $request->input('status', 'all'); // 'all' | 'pending' | 'delivered'
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $perPage  = in_array((int) $request->input('per_page', 15), [15, 25, 50, 100], true)
            ? (int) $request->input('per_page', 15)
            : 15;

        $deliveryStatuses = match ($status) {
            'pending'   => ['EN_PROCESO', 'PENDIENTE'],
            'delivered' => ['ENTREGADO'],
            default     => ['EN_PROCESO', 'PENDIENTE', 'ENTREGADO'],
        };

        $orders = SaleOrder::query()
            ->select('sale_orders.*')
            ->join('deliveries', function ($join) {
                $join->on('deliveries.deliverable_id', '=', 'sale_orders.id')
                     ->where('deliveries.deliverable_type', SaleOrder::class);
            })
            ->where('sale_orders.branch_id', $branchId)
            ->whereIn('deliveries.status', $deliveryStatuses)
            ->when($dateFrom, fn ($q) => $q->whereDate('sale_orders.created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('sale_orders.created_at', '<=', $dateTo))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('sale_orders.number', 'ILIKE', "%{$search}%")
                          ->orWhere('sale_orders.person_name', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByRaw("CASE WHEN deliveries.status IN ('EN_PROCESO', 'PENDIENTE') THEN 0 ELSE 1 END")
            ->orderByDesc('sale_orders.id')
            ->with(['delivery', 'person', 'items'])
            ->paginate($perPage)
            ->withQueryString();

        $paymentMethods    = PaymentMethod::query()->where('status', true)->orderBy('order_num')->get(['id', 'description']);
        $openCashRegisters = $this->getOpenCashRegisters($branchId);
        $digitalWallets    = DigitalWallet::query()->where('status', true)->orderBy('order_num')->get(['id', 'description']);
        $cards             = Card::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'type']);
        $paymentGateways   = PaymentGateways::query()->where('status', true)->orderBy('order_num')->get(['id', 'description']);

        return view('dispatch.index', [
            'orders'            => $orders,
            'search'            => $search,
            'status'            => $status,
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'perPage'           => $perPage,
            'paymentMethods'    => $paymentMethods,
            'openCashRegisters' => $openCashRegisters,
            'digitalWallets'    => $digitalWallets,
            'cards'             => $cards,
            'paymentGateways'   => $paymentGateways,
        ]);
    }

    public function markDelivered(Request $request, SaleOrder $saleOrder)
    {
        abort_if($saleOrder->branch_id !== (int) session('branch_id'), 403);
        abort_if($saleOrder->status === 'cancelled', 422, 'No se puede actualizar la entrega de un pedido cancelado.');

        $validated = $request->validate([
            'tracking_number'    => ['nullable', 'string', 'max:100'],
            'evidence_photo'     => ['nullable', 'image', 'max:5120'],
            'payment_confirmed'  => ['required', 'boolean'],
            'payment_method_id'  => ['nullable', 'integer', 'exists:payment_methods,id'],
            'payment_amount'     => ['nullable', 'numeric', 'min:0.01'],
            'cash_register_id'   => ['nullable', 'integer', 'exists:cash_registers,id'],
            'digital_wallet_id'  => ['nullable', 'integer', 'exists:digital_wallets,id'],
            'card_id'            => ['nullable', 'integer', 'exists:cards,id'],
            'payment_gateway_id' => ['nullable', 'integer', 'exists:payment_gateways,id'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        $balance = round((float) $saleOrder->total - (float) $saleOrder->paid, 2);

        if ((bool) $validated['payment_confirmed'] && $balance > 0.01 && empty($validated['payment_method_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selecciona el método de pago para registrar el cobro.',
            ], 422);
        }

        $paymentAmount = isset($validated['payment_amount'])
            ? round((float) $validated['payment_amount'], 2)
            : $balance;

        if ((bool) $validated['payment_confirmed'] && $balance > 0.01 && $paymentAmount > $balance + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'El monto ingresado supera el saldo pendiente (S/ ' . number_format($balance, 2) . ').',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->hasFile('evidence_photo')) {
                $photoPath = $request->file('evidence_photo')
                    ->store("deliveries/{$saleOrder->id}", 'public');
            }

            $saleOrder->delivery()->updateOrCreate([], [
                'status'            => 'ENTREGADO',
                'delivered_at'      => now(),
                'delivered_by'      => $request->user()?->id,
                'tracking_number'   => $validated['tracking_number'] ?? null,
                'evidence_photo'    => $photoPath,
                'payment_confirmed' => (bool) $validated['payment_confirmed'],
                'notes'             => $validated['notes'] ?? null,
            ]);

            if ((bool) $validated['payment_confirmed'] && $balance > 0.01) {
                $this->registerDeliveryPayment(
                    $saleOrder,
                    $paymentAmount,
                    (int) $validated['payment_method_id'],
                    !empty($validated['cash_register_id']) ? (int) $validated['cash_register_id'] : null,
                    $request->user(),
                    !empty($validated['digital_wallet_id'])  ? (int) $validated['digital_wallet_id']  : null,
                    !empty($validated['card_id'])             ? (int) $validated['card_id']             : null,
                    !empty($validated['payment_gateway_id']) ? (int) $validated['payment_gateway_id'] : null,
                );
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Pedido marcado como entregado.',
                'delivered_at' => now()->format('d/m/Y H:i'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('DispatchController@markDelivered', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar la entrega.'], 500);
        }
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function registerDeliveryPayment(SaleOrder $saleOrder, float $amount, int $paymentMethodId, ?int $cashRegisterId, $user, ?int $digitalWalletId = null, ?int $cardId = null, ?int $paymentGatewayId = null): void
    {
        $paymentMethod  = PaymentMethod::findOrFail($paymentMethodId);
        $digitalWallet  = $digitalWalletId  ? DigitalWallet::find($digitalWalletId)  : null;
        $card           = $cardId           ? Card::find($cardId)                     : null;
        $paymentGateway = $paymentGatewayId ? PaymentGateways::find($paymentGatewayId) : null;

        $payment = SaleOrderPayment::create([
            'sale_order_id'      => $saleOrder->id,
            'amount'             => $amount,
            'payment_method_id'  => $paymentMethod->id,
            'payment_method'     => $paymentMethod->description,
            'digital_wallet_id'  => $digitalWalletId,
            'digital_wallet'     => $digitalWallet?->description,
            'card_id'            => $cardId,
            'payment_gateway_id' => $paymentGatewayId,
            'paid_at'            => now(),
            'created_by'         => $user?->id,
            'created_by_name'    => $user?->name,
        ]);

        if ($cashRegisterId) {
            $cashMovId = $this->createDeliveryCashEntry($saleOrder, $payment, $cashRegisterId, $user, $digitalWalletId, $card, $paymentGateway);
            if ($cashMovId) {
                $payment->cash_movement_id = $cashMovId;
                $payment->save();
            }
        }

        $saleOrder->paid = SaleOrderPayment::where('sale_order_id', $saleOrder->id)->sum('amount');
        $newBalance = (float) $saleOrder->total - (float) $saleOrder->paid;
        if ($saleOrder->status !== 'cancelled') {
            $saleOrder->status = $newBalance <= 0.001
                ? 'completed'
                : ((float) $saleOrder->paid > 0 ? 'partial' : 'draft');
        }
        $saleOrder->save();

        $this->createDeliveryNoteOfSale($saleOrder, $amount, $user, [$payment]);
    }

    private function createDeliveryCashEntry(SaleOrder $saleOrder, SaleOrderPayment $payment, int $cashRegisterId, $user, ?int $digitalWalletId = null, $card = null, $paymentGateway = null): ?int
    {
        $cashRegister = CashRegister::find($cashRegisterId);
        if (!$cashRegister) {
            return null;
        }

        $shift = Shift::where('branch_id', $saleOrder->branch_id)->first() ?? Shift::first();
        if (!$shift) {
            return null;
        }

        $cashMovementTypeId = $this->resolveCashMovementTypeId();
        $cashDocumentTypeId = $this->resolveCashIncomeDocumentTypeId($cashMovementTypeId);
        $paymentConcept     = $this->resolvePaymentConcept();

        $cashEntryMovement = Movement::create([
            'number'            => $this->generateCashMovementNumber($saleOrder->branch_id, $cashRegisterId),
            'moved_at'          => now(),
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Cobro al entregar pedido #' . $saleOrder->number,
            'status'            => '1',
            'movement_type_id'  => $cashMovementTypeId,
            'document_type_id'  => $cashDocumentTypeId,
            'branch_id'         => $saleOrder->branch_id,
            'parent_movement_id'=> null,
        ]);

        $cashMovement = CashMovements::create([
            'payment_concept_id' => $paymentConcept->id,
            'currency'           => 'PEN',
            'exchange_rate'      => 1.000,
            'total'              => (float) $payment->amount,
            'cash_register_id'   => $cashRegisterId,
            'cash_register'      => $cashRegister->number ?? 'Caja Principal',
            'shift_id'           => $shift->id,
            'shift_snapshot'     => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
            'movement_id'        => $cashEntryMovement->id,
            'branch_id'          => $saleOrder->branch_id,
        ]);

        DB::table('cash_movement_details')->insert([
            'cash_movement_id'   => $cashMovement->id,
            'type'               => 'PAGADO',
            'paid_at'            => now(),
            'payment_method_id'  => $payment->payment_method_id,
            'payment_method'     => $payment->payment_method ?? '',
            'number'             => $cashEntryMovement->number,
            'card_id'            => $card?->id,
            'card'               => $card?->description ?? '',
            'bank_id'            => null,
            'bank'               => '',
            'digital_wallet_id'  => $digitalWalletId,
            'digital_wallet'     => $payment->digital_wallet ?? '',
            'payment_gateway_id' => $paymentGateway?->id,
            'payment_gateway'    => $paymentGateway?->description ?? '',
            'amount'             => (float) $payment->amount,
            'comment'            => 'Cobro al entregar pedido #' . $saleOrder->number,
            'status'             => 'A',
            'branch_id'          => $saleOrder->branch_id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $cashMovement->id;
    }

    private function createDeliveryNoteOfSale(SaleOrder $saleOrder, float $amount, $user, array $batchPayments = []): void
    {
        $docTypeId = $this->resolveNotaVentaDocumentTypeId();
        if (!$docTypeId) {
            return;
        }

        $branchId = $saleOrder->branch_id;
        $branch   = Branch::findOrFail($branchId);
        $shift    = Shift::where('branch_id', $branchId)->first() ?? Shift::first();
        if (!$shift) {
            return;
        }

        $movementType = MovementType::query()
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%venta%')->orWhere('description', 'ILIKE', '%sale%'))
            ->orWhere('id', 2)
            ->orderBy('id')
            ->first()
            ?? MovementType::first();

        $number = $this->generateSaleMovementNumber($branchId, $docTypeId);

        $movement = Movement::create([
            'number'            => $number,
            'moved_at'          => now(),
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Pago al entregar pedido #' . $saleOrder->number,
            'status'            => 'A',
            'movement_type_id'  => $movementType->id,
            'document_type_id'  => $docTypeId,
            'branch_id'         => $branchId,
            'parent_movement_id'=> null,
            'shift_id'          => $shift->id,
            'shift_snapshot'    => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
        ]);

        $salesMovement = SalesMovement::create([
            'branch_snapshot' => ['id' => $branch->id, 'legal_name' => $branch->legal_name],
            'series'          => '001',
            'billing_status'  => 'NOT_APPLICABLE',
            'billing_number'  => null,
            'year'            => Carbon::now()->year,
            'detail_type'     => 'DETALLADO',
            'consumption'     => 'N',
            'payment_type'    => 'CONTADO',
            'status'          => 'N',
            'sale_type'       => 'MINORISTA',
            'currency'        => $saleOrder->currency ?? 'PEN',
            'exchange_rate'   => $saleOrder->exchange_rate ?? 1,
            'subtotal'        => round($amount / 1.18, 6),
            'tax'             => round($amount - ($amount / 1.18), 6),
            'total'           => $amount,
            'movement_id'     => $movement->id,
            'branch_id'       => $branchId,
        ]);

        $defaultUnitId = Unit::query()->value('id');
        SalesMovementDetail::create([
            'detail_type'         => 'DETALLADO',
            'sales_movement_id'   => $salesMovement->id,
            'code'                => '',
            'description'         => 'Pago al entregar pedido #' . $saleOrder->number,
            'product_id'          => null,
            'product_snapshot'    => null,
            'unit_id'             => $defaultUnitId,
            'tax_rate_id'         => null,
            'tax_rate_snapshot'   => null,
            'quantity'            => 1,
            'amount'              => $amount,
            'discount_percentage' => 0,
            'original_amount'     => round($amount / 1.18, 6),
            'comment'             => null,
            'parent_detail_id'    => null,
            'complements'         => [],
            'status'              => 'A',
            'branch_id'           => $branchId,
        ]);

        $cashMovementIds = collect($batchPayments)->pluck('cash_movement_id')->filter()->values();
        if ($cashMovementIds->isNotEmpty()) {
            $cashEntryMvtIds = CashMovements::whereIn('id', $cashMovementIds)->pluck('movement_id');
            Movement::whereIn('id', $cashEntryMvtIds)->update(['parent_movement_id' => $movement->id]);
        }

        $noteIds   = $saleOrder->note_movement_ids ?? [];
        $noteIds[] = $movement->id;
        $saleOrder->note_movement_ids = $noteIds;
        $saleOrder->save();
    }

    private function getOpenCashRegisters(int $branchId): \Illuminate\Database\Eloquent\Collection
    {
        $openIds = CashShiftRelation::query()
            ->where('branch_id', $branchId)
            ->where('status', '1')
            ->with('cashMovementStart:id,cash_register_id')
            ->get()
            ->pluck('cashMovementStart.cash_register_id')
            ->filter()
            ->unique()
            ->values();

        return CashRegister::query()
            ->whereIn('id', $openIds)
            ->orderBy('number')
            ->get(['id', 'number', 'series']);
    }

    private function resolveCashMovementTypeId(): int
    {
        $id = MovementType::query()
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%caja%')->orWhere('description', 'ILIKE', '%cash%'))
            ->orderBy('id')
            ->value('id') ?? MovementType::find(4)?->id ?? MovementType::query()->orderBy('id')->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de movimiento de caja.');
        }

        return (int) $id;
    }

    private function resolveCashIncomeDocumentTypeId(int $cashMovementTypeId): int
    {
        $id = DocumentType::query()
            ->where('movement_type_id', $cashMovementTypeId)
            ->where('name', 'ILIKE', '%ingreso%')
            ->orderBy('id')
            ->value('id')
            ?? DocumentType::query()
                ->where('movement_type_id', $cashMovementTypeId)
                ->orderBy('id')
                ->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de documento de ingreso de caja.');
        }

        return (int) $id;
    }

    private function resolvePaymentConcept(): PaymentConcept
    {
        $concept = PaymentConcept::query()
            ->where('type', 'I')
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%cliente%')->orWhere('description', 'ILIKE', '%venta%'))
            ->orderBy('id')
            ->first()
            ?? PaymentConcept::query()->where('type', 'I')->orderBy('id')->first();

        if (!$concept) {
            throw new \Exception('No se encontró concepto de pago de ingreso.');
        }

        return $concept;
    }

    private function resolveNotaVentaDocumentTypeId(): ?int
    {
        return DocumentType::query()
            ->where('movement_type_id', 2)
            ->where(function ($q) {
                $q->where('name', 'ILIKE', '%nota%venta%')
                  ->orWhere('name', 'ILIKE', '%ticket%')
                  ->orWhere('name', 'ILIKE', '%vale%')
                  ->orWhere('name', 'ILIKE', '%nota%');
            })
            ->orderBy('id')
            ->value('id')
            ?? DocumentType::query()
                ->where('movement_type_id', 2)
                ->orderBy('id')
                ->value('id');
    }

    private function generateCashMovementNumber(int $branchId, int $cashRegisterId): string
    {
        $last = Movement::query()
            ->select('movements.number')
            ->join('cash_movements', 'cash_movements.movement_id', '=', 'movements.id')
            ->where('movements.branch_id', $branchId)
            ->where('cash_movements.cash_register_id', $cashRegisterId)
            ->lockForUpdate()
            ->orderByDesc('movements.number')
            ->value('number');

        return str_pad((string) ((int) $last + 1), 8, '0', STR_PAD_LEFT);
    }

    private function generateSaleMovementNumber(int $branchId, int $documentTypeId): string
    {
        $numbers = Movement::query()
            ->where('branch_id', $branchId)
            ->where('document_type_id', $documentTypeId)
            ->lockForUpdate()
            ->pluck('number');

        $last = 0;
        foreach ($numbers as $num) {
            $raw = trim((string) $num);
            if (preg_match('/^\d+$/', $raw) && (int) $raw > $last) {
                $last = (int) $raw;
            }
        }

        return str_pad((string) ($last + 1), 8, '0', STR_PAD_LEFT);
    }
}
