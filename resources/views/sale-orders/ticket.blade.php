<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido {{ $saleOrder->number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #111;
            background: #fff;
        }

        .ticket {
            width: 80mm;
            margin: 0 auto;
            padding: 8px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 1px dashed #555;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header .business-name {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .header .business-ruc {
            font-size: 11px;
            color: #555;
        }
        .header .doc-type {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #111;
            display: inline-block;
            padding: 2px 10px;
        }
        .header .doc-number {
            font-size: 13px;
            font-weight: 700;
        }

        /* Datos del pedido */
        .meta-block {
            margin-bottom: 8px;
            font-size: 11px;
        }
        .meta-block .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .meta-block .label { color: #555; }
        .meta-block .value { font-weight: 600; text-align: right; }

        /* Estado badge */
        .status-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Línea separadora */
        .divider {
            border-top: 1px dashed #555;
            margin: 6px 0;
        }
        .divider-solid {
            border-top: 1px solid #111;
            margin: 6px 0;
        }

        /* Tabla de items */
        .items-header {
            display: flex;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 3px;
            border-bottom: 1px solid #111;
            padding-bottom: 3px;
        }
        .items-header .col-desc  { flex: 1; }
        .items-header .col-qty   { width: 28px; text-align: center; }
        .items-header .col-price { width: 45px; text-align: right; }
        .items-header .col-sub   { width: 50px; text-align: right; }

        .item-row {
            margin-bottom: 3px;
            font-size: 11px;
        }
        .item-row .item-name {
            font-weight: 600;
            word-break: break-word;
        }
        .item-row .item-detail {
            display: flex;
            margin-top: 1px;
        }
        .item-row .col-qty   { width: 28px; text-align: center; color: #555; }
        .item-row .col-price { width: 45px; text-align: right; color: #555; }
        .item-row .col-sub   { width: 50px; text-align: right; font-weight: 700; }
        .item-row .spacer    { flex: 1; }

        /* Totales */
        .totals-block {
            font-size: 11px;
        }
        .totals-block .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .totals-block .total-row.grand {
            font-size: 14px;
            font-weight: 700;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #111;
        }
        .totals-block .total-row.paid   { color: #16a34a; }
        .totals-block .total-row.balance { font-weight: 700; }
        .totals-block .total-row.balance.pending { color: #b45309; }

        /* Pagos */
        .payments-block {
            font-size: 10px;
        }
        .payments-block .pay-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .payments-block .pay-row .pay-method { color: #555; }
        .payments-block .pay-row .pay-amount { font-weight: 600; }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-top: 10px;
        }

        @media print {
            body { background: #fff; }
            .ticket { width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    {{-- Botón imprimir (no aparece al imprimir) --}}
    <div class="no-print" style="padding:12px; text-align:center; background:#f1f5f9;">
        <button onclick="window.print()"
            style="background:linear-gradient(90deg,#ff7a00,#ff4d00); color:#fff; border:none; padding:8px 20px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">
            <i class="ri-printer-line" style="margin-right:4px;"></i> Imprimir
        </button>
        <a href="{{ route('admin.sale-orders.show', $saleOrder) }}"
            style="margin-left:8px; background:#e2e8f0; color:#334155; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:600;">
            Volver
        </a>
    </div>

    <div class="ticket">

        {{-- Encabezado del negocio --}}
        <div class="header">
            <p class="business-name">{{ $saleOrder->branch?->legal_name ?? config('app.name') }}</p>
            @if ($saleOrder->branch?->ruc)
                <p class="business-ruc">RUC: {{ $saleOrder->branch->ruc }}</p>
            @endif
            @if ($saleOrder->branch?->address)
                <p style="font-size:10px; color:#555;">{{ $saleOrder->branch->address }}</p>
            @endif

            <div style="margin-top:6px;">
                <span class="doc-type">Pedido de Venta</span>
            </div>
            <p class="doc-number">N° {{ $saleOrder->number }}</p>
        </div>

        {{-- Datos del pedido --}}
        <div class="meta-block">
            <div class="row">
                <span class="label">Fecha:</span>
                <span class="value">{{ $saleOrder->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if ($saleOrder->due_date)
                <div class="row">
                    <span class="label">Vence:</span>
                    <span class="value">{{ $saleOrder->due_date->format('d/m/Y') }}</span>
                </div>
            @endif
            <div class="row">
                <span class="label">Cliente:</span>
                <span class="value">{{ $saleOrder->person_name ?? 'Público general' }}</span>
            </div>
            <div class="row">
                <span class="label">Atendido por:</span>
                <span class="value">{{ $saleOrder->created_by_name ?? '-' }}</span>
            </div>
            @php
                $statusLabels = [
                    'draft'     => 'Borrador',
                    'partial'   => 'Pago parcial',
                    'completed' => 'Completado',
                    'cancelled' => 'Cancelado',
                ];
            @endphp
            <div class="row" style="margin-top:3px;">
                <span class="label">Estado:</span>
                <span class="value">{{ $statusLabels[$saleOrder->status] ?? $saleOrder->status }}</span>
            </div>
        </div>

        <div class="divider-solid"></div>

        {{-- Cabecera de items --}}
        <div class="items-header">
            <span class="col-desc">Descripción</span>
            <span class="col-qty">Cant</span>
            <span class="col-price">P.Unit</span>
            <span class="col-sub">Total</span>
        </div>

        {{-- Items --}}
        @foreach ($saleOrder->items as $item)
            @php
                $activeQty = (float) $item->quantity - (float) $item->returned_qty;
            @endphp
            <div class="item-row">
                <p class="item-name">{{ $item->product?->description ?? data_get($item->product_snapshot, 'description', '-') }}</p>
                <div class="item-detail">
                    <span class="spacer"></span>
                    <span class="col-qty">{{ number_format($item->quantity, 2) }}</span>
                    <span class="col-price">S/{{ number_format($item->unit_price, 2) }}</span>
                    <span class="col-sub">S/{{ number_format($item->subtotal, 2) }}</span>
                </div>
                @if ((float) $item->returned_qty > 0)
                    <p style="font-size:10px; color:#b45309;">
                        &nbsp;&nbsp;Dev: {{ number_format($item->returned_qty, 2) }} &nbsp;|&nbsp; Activo: {{ number_format($activeQty, 2) }}
                    </p>
                @endif
            </div>
        @endforeach

        <div class="divider-solid"></div>

        {{-- Totales --}}
        <div class="totals-block">
            <div class="total-row grand">
                <span>TOTAL</span>
                <span>S/ {{ number_format($saleOrder->total, 2) }}</span>
            </div>
            <div class="total-row paid">
                <span>Pagado</span>
                <span>S/ {{ number_format($saleOrder->paid, 2) }}</span>
            </div>
            @php $balance = (float)$saleOrder->total - (float)$saleOrder->paid; @endphp
            <div class="total-row balance {{ $balance > 0.001 ? 'pending' : '' }}">
                <span>{{ $balance > 0.001 ? 'SALDO PENDIENTE' : 'SALDADO' }}</span>
                <span>S/ {{ number_format($balance, 2) }}</span>
            </div>
        </div>

        {{-- Pagos --}}
        @if ($saleOrder->payments->count())
            <div class="divider"></div>
            <p style="font-size:10px; font-weight:700; text-transform:uppercase; margin-bottom:3px;">Pagos recibidos</p>
            <div class="payments-block">
                @foreach ($saleOrder->payments as $payment)
                    <div class="pay-row">
                        <span class="pay-method">
                            {{ $payment->payment_method ?? '-' }}
                            @if ($payment->reference) · {{ $payment->reference }} @endif
                            <span style="color:#999;">({{ $payment->paid_at?->format('d/m/Y') ?? '-' }})</span>
                        </span>
                        <span class="pay-amount">S/ {{ number_format($payment->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($saleOrder->notes)
            <div class="divider"></div>
            <p style="font-size:10px; color:#555;">Nota: {{ $saleOrder->notes }}</p>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="divider" style="margin-top:10px;"></div>
            <p>Gracias por su preferencia</p>
            @if ($saleOrder->movement_id)
                <p style="margin-top:2px; font-weight:600;">Facturado · Mov. #{{ $saleOrder->movement_id }}</p>
            @else
                <p style="margin-top:2px; color:#b45309;">Pendiente de facturación</p>
            @endif
            <p style="margin-top:4px; font-size:9px; color:#aaa;">Generado: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

    </div>

</body>
</html>
