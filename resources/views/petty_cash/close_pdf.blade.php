<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja – {{ $cashRegister->number }}</title>
    <style>
        * { font-family: Arial, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; color: #0f172a; font-size: 12px; padding: 20px 24px; }

        /* Header */
        .header { text-align: center; margin-bottom: 12px; }
        .header .company-name { font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .header .company-sub  { font-size: 12px; color: #374151; margin-top: 2px; }
        .header .doc-title    { font-size: 14px; font-weight: 700; margin-top: 6px; letter-spacing: .06em; }

        /* Meta row */
        .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px 16px; margin: 12px 0; font-size: 11px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px; background: #f9fafb; }
        .meta-item .label { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .meta-item .value { font-weight: 600; }

        /* Section titles */
        h2 { font-size: 12px; font-weight: 700; letter-spacing: .06em; text-align: center;
             background: #0f172a; color: #fff; padding: 5px 8px; margin: 14px 0 6px; border-radius: 3px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th { background: #f1f5f9; color: #374151; padding: 5px 8px; text-align: left;
                   font-weight: 700; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
        thead th.r { text-align: right; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tbody td.r { text-align: right; }
        tfoot td { padding: 6px 8px; font-weight: 700; border-top: 2px solid #0f172a; font-size: 12px; background: #f8fafc; }
        tfoot td.r { text-align: right; }

        /* Summary grid */
        .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .summary-card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
        .summary-card .s-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .summary-card .s-value { font-size: 16px; font-weight: 700; margin-top: 3px; }
        .s-value.green  { color: #059669; }
        .s-value.blue   { color: #2563eb; }
        .s-value.orange { color: #d97706; }
        .s-value.red    { color: #dc2626; }
        .s-value.dark   { color: #0f172a; }

        /* Balance by method sub-row */
        .sub-detail { font-size: 10px; color: #64748b; }

        .footer { margin-top: 20px; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

    {{-- Header --}}
    @php
        $companyName   = $company?->legal_name ?? $branch?->legal_name ?? '';
        $ruc           = $company?->tax_id ?? $branch?->ruc ?? '';
        $branchAddress = $branch?->address ?? $company?->address ?? '';
        $branchName    = $branch?->legal_name ?? '';
    @endphp
    <div class="header">
        <div class="company-name">{{ $companyName }}</div>
        @if($ruc)<div class="company-sub">RUC: {{ $ruc }}</div>@endif
        @if($branchAddress)<div class="company-sub">{{ $branchAddress }}</div>@endif
        <div class="doc-title">REPORTE DE CIERRE DE CAJA</div>
    </div>

    {{-- Meta --}}
    <div class="meta-grid">
        <div class="meta-item">
            <div class="label">Sucursal</div>
            <div class="value">{{ $branchName ?: '-' }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Caja</div>
            <div class="value">{{ $cashRegister->number }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Turno</div>
            <div class="value">{{ $shiftName }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Cajero</div>
            <div class="value">{{ $responsibleLabel }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Apertura</div>
            <div class="value">{{ \Carbon\Carbon::parse($startedAt)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Cierre</div>
            <div class="value">{{ $closedAt->format('d/m/Y H:i') }}</div>
        </div>
        <div class="meta-item">
            <div class="label">Impresión</div>
            <div class="value">{{ $printedAt->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    {{-- COMPROBANTES PEN --}}
    <h2>COMPROBANTES PEN</h2>
    @php
        $totalDocCount  = array_sum(array_column($docCounts, 'count'));
        $totalDocAmount = array_sum(array_column($docCounts, 'amount'));
    @endphp
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th class="r">Cantidad</th>
                <th class="r">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($docCounts as $label => $doc)
            <tr>
                <td>{{ $label }}</td>
                <td class="r">{{ $doc['count'] }}</td>
                <td class="r">S/ {{ number_format($doc['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="r">{{ $totalDocCount }}</td>
                <td class="r">S/ {{ number_format($totalDocAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- FORMAS DE PAGO VENTAS PEN --}}
    <h2>FORMAS DE PAGO VENTAS PEN</h2>
    @php $totalSalesAllMethods = array_sum($salesByMethod); @endphp
    <table>
        <thead>
            <tr>
                <th>Medio de Pago</th>
                <th class="r">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesByMethod as $method => $amount)
            <tr>
                <td>{{ $method }}</td>
                <td class="r">S/ {{ number_format($amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="r">S/ {{ number_format($totalSalesAllMethods, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- SALDO POR MEDIO PEN --}}
    <h2>SALDO POR MEDIO PEN</h2>
    <table>
        <thead>
            <tr>
                <th>Medio</th>
                <th class="r">Ventas</th>
                <th class="r">Ingresos</th>
                <th class="r">Egresos</th>
                <th class="r">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($balanceByMethod as $method => $b)
            @php $net = $b['ventas'] + $b['ingresos'] - $b['egresos']; @endphp
            <tr>
                <td>{{ $method }}</td>
                <td class="r">S/ {{ number_format($b['ventas'], 2) }}</td>
                <td class="r">S/ {{ number_format($b['ingresos'], 2) }}</td>
                <td class="r">S/ {{ number_format($b['egresos'], 2) }}</td>
                <td class="r"><strong>S/ {{ number_format($net, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- RESUMEN EFECTIVO PEN --}}
    <h2>RESUMEN EFECTIVO PEN</h2>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="s-label">Apertura</div>
            <div class="s-value blue">S/ {{ number_format($openingCash, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="s-label">Efectivo Ventas</div>
            <div class="s-value green">S/ {{ number_format($cashSales, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="s-label">Otros Ingresos</div>
            <div class="s-value orange">S/ {{ number_format($otherCashIncome, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="s-label">Egresos</div>
            <div class="s-value red">S/ {{ number_format($cashExpenses, 2) }}</div>
        </div>
    </div>
    <div style="margin-top:8px;">
        <div class="summary-card" style="border-color:#0f172a;">
            <div class="s-label">Total Efectivo en Caja</div>
            <div class="s-value dark" style="font-size:20px;">S/ {{ number_format($systemCash, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        Generado el {{ $printedAt->format('d/m/Y \a \l\a\s H:i:s') }} &nbsp;·&nbsp; Caja {{ $cashRegister->number }}
    </div>

</body>
</html>
