<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Cierre Caja</title>
    <style>
        * { font-family: Verdana, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }

        html { background: #e5e7eb; }
        body { background: #e5e7eb; display: flex; justify-content: center; padding: 8mm 0; }

        /* Caja del ticket: siempre 80mm */
        .ticket { width: 80mm; background: #fff; padding: 2mm 2.5mm 3mm; }

        /* Tipografía base en pt para escalar bien en impresora */
        p   { font-size: 7.5pt; margin: 1px 0; line-height: 1.35; }

        .center { text-align: center; }
        .bold   { font-weight: bold; }
        .right  { text-align: right; }

        .company-name { font-size: 11pt; font-weight: 700; line-height: 1.2; }
        .company-sub  { font-size: 7.5pt; }
        .doc-title    { font-size: 9pt; font-weight: 700; margin: 3px 0 2px; }

        /* Separadores */
        .sep-eq {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            overflow: hidden;
            white-space: nowrap;
            margin: 3px 0;
            line-height: 1;
        }
        .sep-dash { border-top: 1px dashed #888; margin: 4px 0; }

        /* Título de sección */
        .section-title { font-size: 8pt; font-weight: 700; text-align: center; margin: 1px 0; }

        /* Tabla base */
        table { width: 100%; border-collapse: collapse; }
        td    { font-size: 7.5pt; padding: 1px 0; vertical-align: middle; line-height: 1.35; }

        /* Meta: label izq, valor der */
        .meta-label { font-weight: 700; white-space: nowrap; }
        .meta-value { text-align: right; }

        /* Comprobantes: 3 col */
        .col-tipo { width: auto; }
        .col-cant { width: 18pt; text-align: right; white-space: nowrap; font-size: 7pt; }
        .col-imp  { width: 36pt; text-align: right; white-space: nowrap; }

        /* Encabezado de comprobantes */
        .thead-comp td { font-weight: 700; font-size: 7pt; border-bottom: 1px dashed #888; padding-bottom: 2px; }

        /* Total con borde superior */
        .tfoot-total td { font-weight: 700; font-size: 8pt; border-top: 1px solid #000; padding-top: 2px; }

        /* Datos 2 col: label izq, monto der */
        .col-lbl { width: auto; }
        .col-mto { width: 44pt; text-align: right; white-space: nowrap; font-weight: 700; }

        /* Sub-fila V/I/E */
        .sub-row { font-size: 6.5pt; color: #444; padding: 0 0 2px; }

        /* Total grande efectivo */
        .grand-total td { font-size: 9pt; font-weight: 700; border-top: 1.5px solid #000; padding-top: 3px; }

        /* Footer */
        .footer { font-size: 6.5pt; text-align: center; margin-top: 5px; line-height: 1.5; color: #333; }

        /* ─── PRINT ─── */
        @media print {
            html, body { background: #fff; display: block; padding: 0; margin: 0; width: 80mm; }
            @page { size: 80mm auto; margin: 0; }
            .ticket { width: 100%; padding: 1mm 2mm 2mm; }
        }
    </style>
    @if(!empty($autoPrint))
    <script>window.addEventListener('load', function(){ window.print(); });</script>
    @endif
</head>
<body>
<div class="ticket">

    @php
        $companyName   = $company?->legal_name ?? $branch?->legal_name ?? '';
        $ruc           = $company?->tax_id ?? $branch?->ruc ?? '';
        $branchAddress = $branch?->address ?? $company?->address ?? '';
        $branchName    = $branch?->legal_name ?? '';
        $eq            = str_repeat('=', 200);

        $totalDocCount      = array_sum(array_column($docCounts, 'count'));
        $totalDocAmount     = array_sum(array_column($docCounts, 'amount'));
        $totalSalesMethods  = array_sum($salesByMethod);
    @endphp

    {{-- Encabezado --}}
    <div class="center">
        <p class="company-name">{{ strtoupper($companyName) }}</p>
        @if($ruc)<p class="company-sub">RUC: {{ $ruc }}</p>@endif
        @if($branchAddress)<p class="company-sub">{{ $branchAddress }}</p>@endif
        <p class="doc-title bold">CIERRE DE CAJA</p>
    </div>

    <div class="sep-dash"></div>

    {{-- Meta info --}}
    <table>
        <tr><td class="meta-label">Sucursal:</td> <td class="meta-value">{{ $branchName ?: '-' }}</td></tr>
        <tr><td class="meta-label">Caja:</td>     <td class="meta-value">{{ $cashRegister->number }}</td></tr>
        <tr><td class="meta-label">Cajero:</td>   <td class="meta-value">{{ $responsibleLabel }}</td></tr>
        <tr><td class="meta-label">Turno:</td>    <td class="meta-value">{{ $shiftName }}</td></tr>
        <tr><td class="meta-label">Apertura:</td> <td class="meta-value">{{ \Carbon\Carbon::parse($startedAt)->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="meta-label">Cierre:</td>   <td class="meta-value">{{ $closedAt->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="meta-label">Impresión:</td><td class="meta-value">{{ $printedAt->format('d/m/Y H:i') }}</td></tr>
    </table>

    {{-- ═══ COMPROBANTES PEN ═══ --}}
    <div class="sep-eq">{{ $eq }}</div>
    <p class="section-title">COMPROBANTES PEN</p>
    <div class="sep-eq">{{ $eq }}</div>

    <table>
        <thead class="thead-comp">
            <tr>
                <td class="col-tipo">Tipo</td>
                <td class="col-cant">N°</td>
                <td class="col-imp">Monto</td>
            </tr>
        </thead>
        <tbody>
            @foreach($docCounts as $label => $doc)
            <tr>
                <td class="col-tipo">{{ $label }}</td>
                <td class="col-cant">{{ $doc['count'] }}</td>
                <td class="col-imp">{{ number_format($doc['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="tfoot-total">
            <tr>
                <td class="col-tipo">TOTAL</td>
                <td class="col-cant">{{ $totalDocCount }}</td>
                <td class="col-imp">{{ number_format($totalDocAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ═══ FORMAS DE PAGO VENTAS PEN ═══ --}}
    <div class="sep-eq">{{ $eq }}</div>
    <p class="section-title">FORMAS DE PAGO VENTAS PEN</p>
    <div class="sep-eq">{{ $eq }}</div>

    <table>
        <tbody>
            @foreach($salesByMethod as $method => $amount)
            <tr>
                <td class="col-lbl">{{ $method }}</td>
                <td class="col-mto">{{ number_format($amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="tfoot-total">
            <tr>
                <td class="col-lbl">TOTAL</td>
                <td class="col-mto">{{ number_format($totalSalesMethods, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ═══ SALDO POR MEDIO PEN ═══ --}}
    <div class="sep-eq">{{ $eq }}</div>
    <p class="section-title">SALDO POR MEDIO PEN</p>
    <div class="sep-eq">{{ $eq }}</div>

    <table>
        <tbody>
            @foreach($balanceByMethod as $method => $b)
            @php $net = $b['ventas'] + $b['ingresos'] - $b['egresos']; @endphp
            <tr>
                <td class="col-lbl bold">{{ $method }}</td>
                <td class="col-mto">{{ number_format($net, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="sub-row">V:{{ number_format($b['ventas'], 2) }} I:{{ number_format($b['ingresos'], 2) }} E:{{ number_format($b['egresos'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ═══ RESUMEN EFECTIVO PEN ═══ --}}
    <div class="sep-eq">{{ $eq }}</div>
    <p class="section-title">RESUMEN EFECTIVO PEN</p>
    <div class="sep-eq">{{ $eq }}</div>

    <table>
        <tbody>
            <tr><td class="col-lbl">APERTURA</td>        <td class="col-mto">{{ number_format($openingCash, 2) }}</td></tr>
            <tr><td class="col-lbl">EFECTIVO VENTAS</td> <td class="col-mto">{{ number_format($cashSales, 2) }}</td></tr>
            <tr><td class="col-lbl">OTROS INGRESOS</td>  <td class="col-mto">{{ number_format($otherCashIncome, 2) }}</td></tr>
            <tr><td class="col-lbl">EGRESOS</td>         <td class="col-mto">{{ number_format($cashExpenses, 2) }}</td></tr>
        </tbody>
        <tfoot class="grand-total">
            <tr>
                <td class="col-lbl">TOTAL EFECTIVO</td>
                <td class="col-mto">{{ number_format($systemCash, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sep-eq">{{ $eq }}</div>

    <div class="footer">
        Impreso: {{ $printedAt->format('d/m/Y H:i:s') }}<br>
        -- Fin del Turno --
    </div>

</div>
</body>
</html>
