@extends('layouts.app')

@section('content')
    @php
        $statusMap = [
            'draft'     => ['label' => 'Pago Pendiente', 'class' => 'bg-slate-100 text-slate-700'],
            'partial'   => ['label' => 'Pago parcial', 'class' => 'bg-amber-100 text-amber-700'],
            'completed' => ['label' => 'Completado', 'class' => 'bg-emerald-100 text-emerald-700'],
            'cancelled' => ['label' => 'Cancelado',  'class' => 'bg-red-100 text-red-700'],
        ];
        $st      = $statusMap[$saleOrder->status] ?? ['label' => $saleOrder->status, 'class' => 'bg-gray-100 text-gray-700'];
        $balance = (float) $saleOrder->total - (float) $saleOrder->paid;
        $canPay     = $balance > 0.001 && $saleOrder->status !== 'cancelled';
        $canInvoice = $saleOrder->movement_id === null && $saleOrder->status !== 'cancelled';
        $canCancel  = $saleOrder->movement_id === null && !in_array($saleOrder->status, ['completed', 'cancelled']);

        // Returnable qty per item: payments "cover" units sequentially (ceil rule).
        // Units touched by any payment cannot be returned.
        $paidRemaining = (float) $saleOrder->paid;
        $returnablePerId = [];
        foreach ($saleOrder->items->sortBy('id') as $_item) {
            $activeQty = max(0.0, (float)$_item->quantity - (float)$_item->returned_qty);
            $unitPrice = (float)$_item->unit_price;
            if ($activeQty <= 0 || $unitPrice <= 0) {
                $returnablePerId[$_item->id] = 0.0;
                continue;
            }
            if ($paidRemaining <= 0) {
                $returnablePerId[$_item->id] = $activeQty;
            } else {
                $coveredUnits = (int) ceil(min($paidRemaining, $unitPrice * $activeQty) / $unitPrice);
                $returnablePerId[$_item->id] = max(0.0, $activeQty - $coveredUnits);
                $paidRemaining = max(0.0, $paidRemaining - $unitPrice * $coveredUnits);
            }
        }
        $canReturn = !in_array($saleOrder->status, ['completed', 'cancelled'])
            && array_sum($returnablePerId) > 0.000001;

        // Map payment_method_id → 'card' | 'wallet' | null
        $payMethodTypes = [];
        foreach ($paymentMethods as $pm) {
            $d = strtolower($pm->description);
            if (str_contains($d, 'tarjeta')) {
                $payMethodTypes[(string)$pm->id] = 'card';
            } elseif (str_contains($d, 'billetera') || str_contains($d, 'digital') || str_contains($d, 'wallet')) {
                $payMethodTypes[(string)$pm->id] = 'wallet';
            } else {
                $payMethodTypes[(string)$pm->id] = null;
            }
        }
    @endphp

    <div x-data="soShowPage()" id="so-show-view">

        <x-common.page-breadcrumb
            pageTitle="Pedido {{ $saleOrder->number }}"
            :crumbs="[
                ['label' => 'Pedidos de Venta', 'url' => route('admin.sale-orders.index')],
                ['label' => 'Pedido ' . $saleOrder->number],
            ]"
        />

        {{-- ── Cabecera del pedido ─────────────────────────────────────────── --}}
        <x-common.component-card title="Pedido {{ $saleOrder->number }}" desc="Detalle del pedido de venta a crédito.">

            @php
                $deliveryMap = [
                    'PENDIENTE'  => ['label' => 'Recojo pendiente', 'class' => 'bg-amber-100 text-amber-700'],
                    'EN_PROCESO' => ['label' => 'Por enviar',       'class' => 'bg-blue-100 text-blue-700'],
                    'ENTREGADO'  => ['label' => 'Entregado',        'class' => 'bg-emerald-100 text-emerald-700'],
                ];
                $ds = $deliveryMap[$saleOrder->delivery?->status] ?? null;
            @endphp

            <div class="flex gap-4">
                <div class="flex-1 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Estado</p>
                    <p class="mt-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold {{ $st['class'] }}">{{ $st['label'] }}</span>
                        @if ($saleOrder->movement_id)
                            <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                <i class="ri-file-text-line mr-1"></i> Facturado
                            </span>
                        @endif
                    </p>
                </div>
                <div class="flex-1 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Entrega</p>
                    <p class="mt-2">
                        @if ($ds)
                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-bold {{ $ds['class'] }}">
                                <i class="ri-truck-line"></i> {{ $ds['label'] }}
                            </span>
                        @else
                            <span class="text-sm text-slate-400">—</span>
                        @endif
                    </p>
                    @if ($saleOrder->delivery?->pickup_at)
                        <p class="mt-1 text-xs text-slate-500">
                            <i class="ri-calendar-line"></i> {{ $saleOrder->delivery->pickup_at->format('d/m/Y H:i') }}
                        </p>
                    @endif
                    @if ($saleOrder->delivery?->pickup_responsible)
                        <p class="mt-0.5 text-xs text-slate-500">
                            <i class="ri-user-line"></i> {{ $saleOrder->delivery->pickup_responsible }}
                        </p>
                    @endif
                    @if ($saleOrder->delivery?->shipping_address)
                        <p class="mt-0.5 text-xs text-slate-500">
                            <i class="ri-map-pin-line"></i> {{ $saleOrder->delivery->shipping_address }}
                        </p>
                    @endif
                    @if ($saleOrder->delivery?->evidence_photo)
                        <a href="{{ asset('storage/' . $saleOrder->delivery->evidence_photo) }}" target="_blank" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
                            <i class="ri-image-line"></i> Foto Venta
                        </a>
                    @endif
                    @if ($saleOrder->delivery?->dispatch_evidence_photo)
                        <a href="{{ asset('storage/' . $saleOrder->delivery->dispatch_evidence_photo) }}" target="_blank" class="mt-1.5 ml-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                            <i class="ri-image-2-line"></i> Foto Entrega
                        </a>
                    @endif
                </div>
                <div class="flex-1 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Cliente</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $saleOrder->person_name ?? 'Público general' }}</p>
                </div>
                <div class="flex-1 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Vencimiento</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $saleOrder->due_date?->format('d/m/Y') ?? '-' }}</p>
                </div>
                <div class="flex-1 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Creado por</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $saleOrder->created_by_name ?? '-' }}</p>
                    <p class="text-xs text-slate-400">{{ $saleOrder->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Totales --}}
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Total</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">S/ {{ number_format($saleOrder->total, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-600">Pagado</p>
                    <p class="mt-2 text-2xl font-black text-emerald-700">S/ {{ number_format($saleOrder->paid, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 {{ $balance > 0.001 ? 'bg-amber-50' : 'bg-emerald-50' }} px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] {{ $balance > 0.001 ? 'text-amber-700' : 'text-emerald-600' }}">
                        {{ $balance > 0.001 ? 'Saldo pendiente' : 'Saldado' }}
                    </p>
                    <p class="mt-2 text-2xl font-black {{ $balance > 0.001 ? 'text-amber-700' : 'text-emerald-700' }}">
                        S/ {{ number_format($balance, 2) }}
                    </p>
                </div>
            </div>

            @if ($saleOrder->notes)
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Notas</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $saleOrder->notes }}</p>
                </div>
            @endif

            {{-- Acciones rápidas --}}
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($canPay)
                    <button type="button" @click="openPaymentModal()"
                        class="inline-flex h-10 items-center gap-2 rounded-xl px-4 text-sm font-semibold text-white shadow-theme-xs"
                        style="background:linear-gradient(90deg,#22c55e,#16a34a);">
                        <i class="ri-money-dollar-circle-line"></i>
                        <span>Registrar pago</span>
                    </button>
                @endif
                @if ($canReturn)
                    <button type="button" @click="openReturnModal()"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                        <i class="ri-arrow-go-back-line"></i>
                        <span>Registrar devolución</span>
                    </button>
                @endif
                @if ($canInvoice)
                    <button type="button" @click="openInvoiceModal()"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                        <i class="ri-file-text-line"></i>
                        <span>Facturar pedido</span>
                    </button>
                @endif
                @if ($saleOrder->status !== 'cancelled')
                    @if ($saleOrder->delivery?->status !== 'ENTREGADO')
                        <button type="button" @click="openDeliveryConfirmModal()" :disabled="deliveryLoading"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-60">
                            <i class="ri-truck-line"></i>
                            <span x-text="deliveryLoading ? 'Guardando...' : '{{ in_array($saleOrder->delivery?->status, ['PENDIENTE', 'EN_PROCESO']) ? 'Marcar como Entregado' : 'Registrar entrega' }}'"></span>
                        </button>
                    @elseif ($saleOrder->status !== 'completed')
                        <button type="button" @click="markDelivery('PENDIENTE')" :disabled="deliveryLoading"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-60">
                            <i class="ri-refresh-line"></i>
                            <span x-text="deliveryLoading ? 'Guardando...' : 'Revertir entrega'"></span>
                        </button>
                    @endif
                @endif
                <a href="{{ route('admin.sale-orders.ticket', $saleOrder) }}" target="_blank"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="ri-printer-line"></i>
                    <span>Imprimir ticket</span>
                </a>
                @if ($canCancel)
                    <button type="button" @click="confirmCancel()"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 hover:bg-red-100">
                        <i class="ri-close-circle-line"></i>
                        <span>Cancelar pedido</span>
                    </button>
                @endif
                <a href="{{ route('admin.sale-orders.index') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="ri-arrow-left-line"></i>
                    <span>Volver</span>
                </a>
            </div>
        </x-common.component-card>

        {{-- ── Items ────────────────────────────────────────────────────────── --}}
        <x-common.component-card title="Productos del pedido" desc="Líneas de detalle.">
            <div class="table-responsive rounded-xl border border-gray-200 bg-white">
                <table class="w-full" style="min-width: 640px;">
                    <thead>
                        <tr style="background:linear-gradient(90deg,#ff7a00,#ff4d00);">
                            @foreach(['Producto', 'Cantidad', 'Devuelto', 'Activo', 'Precio unit.', 'Subtotal'] as $h)
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-white">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($saleOrder->items as $item)
                            @php
                                $activeQty = (float) $item->quantity - (float) $item->returned_qty;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ $item->product?->description ?? data_get($item->product_snapshot, 'description', '-') }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-slate-700">
                                    {{ number_format($item->quantity, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-amber-600">
                                    {{ number_format($item->returned_qty, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-bold text-slate-900">
                                    {{ number_format($activeQty, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700 whitespace-nowrap">
                                    S/ {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-slate-900 whitespace-nowrap">
                                    S/ {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-slate-900">Total</td>
                            <td class="px-4 py-3 text-right text-base font-black text-orange-600 whitespace-nowrap">
                                S/ {{ number_format($saleOrder->total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-common.component-card>

        {{-- ── Pagos ────────────────────────────────────────────────────────── --}}
        @if ($saleOrder->payments->count())
            <x-common.component-card title="Historial de pagos" desc="Pagos registrados para este pedido.">
                <div class="table-responsive rounded-xl border border-gray-200 bg-white">
                    <table class="w-full" style="min-width: 560px;">
                        <thead>
                            <tr style="background:linear-gradient(90deg,#22c55e,#16a34a);">
                                @foreach(['Fecha', 'Método', 'Referencia', 'Registrado por', 'Monto'] as $h)
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-white">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($saleOrder->payments as $payment)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center text-sm text-slate-500 whitespace-nowrap">
                                        {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $payment->payment_method ?? '-' }}
                                        @if ($payment->card) <span class="text-xs text-slate-400">· {{ $payment->card }}</span> @endif
                                        @if ($payment->digital_wallet) <span class="text-xs text-slate-400">· {{ $payment->digital_wallet }}</span> @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-slate-500">
                                        {{ $payment->reference ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-500">
                                        {{ $payment->created_by_name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-emerald-700 whitespace-nowrap">
                                        S/ {{ number_format($payment->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-common.component-card>
        @endif

        {{-- ── Devoluciones ─────────────────────────────────────────────────── --}}
        @if ($saleOrder->returns->count())
            <x-common.component-card title="Devoluciones" desc="Productos devueltos de este pedido.">
                <div class="table-responsive rounded-xl border border-gray-200 bg-white">
                    <table class="w-full" style="min-width: 560px;">
                        <thead>
                            <tr style="background:linear-gradient(90deg,#f59e0b,#d97706);">
                                @foreach(['Fecha', 'Producto', 'Cantidad', 'Precio unit.', 'Subtotal', 'Notas'] as $h)
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-white">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($saleOrder->returns as $return)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center text-sm text-slate-500 whitespace-nowrap">
                                        {{ $return->returned_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $return->saleOrderItem?->product?->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-slate-700">
                                        {{ number_format($return->quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-slate-700 whitespace-nowrap">
                                        S/ {{ number_format($return->unit_price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-amber-700 whitespace-nowrap">
                                        S/ {{ number_format($return->subtotal, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-400">{{ $return->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-common.component-card>
        @endif


        {{-- ══ MODAL: Registrar pago ══════════════════════════════════════════ --}}
        <div x-show="paymentModalOpen" x-cloak
            class="fixed inset-0 z-[100000] overflow-hidden p-3 sm:p-6">
            <div class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"
                @click="paymentModalOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">

                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Registrar pago</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Saldo: <span class="font-bold text-amber-500">S/ {{ number_format($balance, 2) }}</span>
                            </p>
                        </div>
                        <button type="button" @click="paymentModalOpen = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="px-5 py-4 space-y-3 max-h-[70vh] overflow-y-auto">

                        {{-- Selector de caja --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">
                                Caja <span class="text-red-400">*</span>
                            </label>
                            @if($openCashRegisters->isEmpty())
                                <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-medium text-amber-700">
                                    <i class="ri-error-warning-line flex-none"></i>
                                    <span>No hay cajas abiertas. Abre una caja antes de registrar el pago.</span>
                                </div>
                            @else
                                <select x-model="payForm.cash_register_id"
                                    class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700 outline-none focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                    @foreach ($openCashRegisters as $cr)
                                        <option value="{{ $cr->id }}">
                                            Caja {{ $cr->number }}{{ $cr->series ? ' — ' . $cr->series : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- Métodos de pago --}}
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Métodos de pago</p>
                            <button type="button"
                                x-show="payForm.methods.length < 4"
                                @click="addPayMethod()"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                                + Agregar otro
                            </button>
                        </div>

                        {{-- Fila por método --}}
                        <template x-for="(method, index) in payForm.methods" :key="index">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    {{-- Selector de método --}}
                                    <select x-model="method.payment_method_id"
                                        @change="onMethodChange(method)"
                                        class="h-11 flex-1 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700 outline-none focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Método...</option>
                                        @foreach ($paymentMethods as $pm)
                                            <option value="{{ $pm->id }}">{{ $pm->description }}</option>
                                        @endforeach
                                    </select>

                                    {{-- Monto con prefijo separado --}}
                                    <div class="flex w-32 flex-none overflow-hidden rounded-xl border border-gray-200 bg-gray-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100">
                                        <span class="flex h-11 items-center border-r border-gray-200 bg-gray-100 px-2.5 text-xs font-bold text-gray-500">S/</span>
                                        <input type="number" x-model="method.amount" min="1" step="0.01"
                                            placeholder="0.00"
                                            class="h-11 w-full bg-transparent px-2.5 text-sm font-semibold text-gray-800 outline-none">
                                    </div>

                                    {{-- Papelera --}}
                                    <button type="button"
                                        x-show="payForm.methods.length > 1"
                                        @click="removePayMethod(index)"
                                        class="flex h-11 w-11 flex-none items-center justify-center rounded-xl text-red-400 hover:bg-red-50 hover:text-red-600">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                    <div x-show="payForm.methods.length === 1" class="w-11 flex-none"></div>
                                </div>

                                {{-- Sub-select: Tarjeta --}}
                                <template x-if="payMethodTypes[method.payment_method_id] === 'card'">
                                    <select x-model="method.card_id"
                                        class="h-10 w-full rounded-xl border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-800 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                        <option value="">Selecciona la tarjeta...</option>
                                        @foreach ($cards as $card)
                                            @php
                                                $cardTypeLabel = match(strtoupper($card->type)) {
                                                    'C' => 'Crédito',
                                                    'D' => 'Débito',
                                                    default => $card->type,
                                                };
                                            @endphp
                                            <option value="{{ $card->id }}">{{ $card->description }} — {{ $cardTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                </template>

                                {{-- Sub-select: Billetera digital --}}
                                <template x-if="payMethodTypes[method.payment_method_id] === 'wallet'">
                                    <select x-model="method.digital_wallet_id"
                                        class="h-10 w-full rounded-xl border border-violet-200 bg-violet-50 px-3 text-sm font-medium text-violet-800 outline-none focus:border-violet-400 focus:bg-white focus:ring-2 focus:ring-violet-100">
                                        <option value="">Selecciona la billetera...</option>
                                        @foreach ($digitalWallets as $dw)
                                            <option value="{{ $dw->id }}">{{ $dw->description }}</option>
                                        @endforeach
                                    </select>
                                </template>

                                {{-- Referencia --}}
                                <input type="text" x-model="method.reference" maxlength="100"
                                    placeholder="Nro. de operación (opcional)"
                                    class="h-9 w-full rounded-xl border border-gray-100 bg-gray-50 px-3 text-xs text-gray-500 outline-none focus:border-emerald-300 focus:bg-white focus:ring-1 focus:ring-emerald-100">
                            </div>
                        </template>

                        {{-- Total distribuido --}}
                        <div class="border-t border-dashed border-gray-200 pt-3 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-500">Total distribuido</span>
                                <span class="text-base font-black"
                                    :class="totalDistributed > {{ $balance }} + 0.001 ? 'text-red-600' : (totalDistributed > 0 ? 'text-emerald-600' : 'text-gray-300')"
                                    x-text="'S/ ' + totalDistributed.toFixed(2)"></span>
                            </div>
                            <div x-show="totalDistributed > {{ $balance }} + 0.001"
                                class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600">
                                <i class="ri-error-warning-line"></i>
                                <span>Supera el saldo pendiente de <strong>S/ {{ number_format($balance, 2) }}</strong></span>
                            </div>
                        </div>

                        {{-- Fecha --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Fecha y hora</label>
                            <input type="datetime-local" x-model="payForm.paid_at"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                        </div>

                        {{-- Notas --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Notas <span class="font-normal">(opcional)</span></label>
                            <textarea x-model="payForm.notes" rows="2" placeholder="Observaciones..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-emerald-400 focus:bg-white focus:ring-2 focus:ring-emerald-100 resize-none"></textarea>
                        </div>

                        {{-- Generar comprobante (solo visible cuando el pago completa el saldo) --}}
                        <div x-show="totalDistributed >= {{ $balance }} - 0.001" x-cloak>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 hover:bg-emerald-100 transition-colors">
                                <input type="checkbox" x-model="payForm.generate_invoice"
                                    class="h-4 w-4 flex-none rounded border-gray-300 text-emerald-600 focus:ring-emerald-400">
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">Generar boleta/factura del total</p>
                                    <p class="text-xs text-emerald-600">Las notas de venta anteriores quedarán inactivas y se emitirá el comprobante por S/ {{ number_format($saleOrder->total, 2) }}</p>
                                </div>
                            </label>
                        </div>

                        {{-- Error --}}
                        <div x-show="payError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                            x-text="payError"></div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                        <button type="button" @click="paymentModalOpen = false"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600">
                            Cancelar
                        </button>
                        <button type="button" @click="submitPayment()" :disabled="payLoading"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-5 text-sm font-bold text-white disabled:opacity-60"
                            style="background:linear-gradient(90deg,#22c55e,#16a34a);">
                            <i class="ri-money-dollar-circle-line"></i>
                            <span x-text="payLoading ? 'Guardando...' : 'Guardar pago'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: Registrar devolución ════════════════════════════════════ --}}
        <div x-show="returnModalOpen" x-cloak
            class="fixed inset-0 z-[100000] overflow-hidden p-3 sm:p-6">
            <div class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"
                @click="returnModalOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">

                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Registrar devolución</h3>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $saleOrder->number }}</p>
                        </div>
                        <button type="button" @click="returnModalOpen = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="px-5 py-4 space-y-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Producto <span class="text-red-400">*</span></label>
                            <select x-model="retForm.sale_order_item_id"
                                @change="retForm.quantity = ''"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700 outline-none focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-100">
                                <option value="">Seleccione producto...</option>
                                @foreach ($saleOrder->items->sortBy('id') as $item)
                                    @php $retQty = $returnablePerId[$item->id] ?? 0; @endphp
                                    @if ($retQty > 0)
                                        <option value="{{ $item->id }}">
                                            {{ $item->product?->description ?? data_get($item->product_snapshot,'description','-') }}
                                            — máx. devolvible: {{ number_format($retQty, 2) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">
                                Cantidad a devolver <span class="text-red-400">*</span>
                                <span class="font-normal text-amber-500 ml-1"
                                    x-show="retForm.sale_order_item_id"
                                    x-text="'(máx: ' + (returnableQtys[retForm.sale_order_item_id] ?? 0).toFixed(2) + ')'"></span>
                            </label>
                            <input type="number" x-model="retForm.quantity" min="0.001" step="0.001"
                                :max="returnableQtys[retForm.sale_order_item_id] ?? undefined"
                                placeholder="0.00"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-semibold text-gray-800 outline-none focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-100">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Notas <span class="font-normal">(opcional)</span></label>
                            <textarea x-model="retForm.notes" rows="2" placeholder="Motivo de la devolución..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-100 resize-none"></textarea>
                        </div>

                        <div x-show="retError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                            x-text="retError"></div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                        <button type="button" @click="returnModalOpen = false"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600">
                            Cancelar
                        </button>
                        <button type="button" @click="submitReturn()" :disabled="retLoading"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-5 text-sm font-bold text-white disabled:opacity-60"
                            style="background:linear-gradient(90deg,#f59e0b,#d97706);">
                            <i class="ri-arrow-go-back-line"></i>
                            <span x-text="retLoading ? 'Guardando...' : 'Guardar devolución'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: Cancelar pedido ═════════════════════════════════════════ --}}
        <div x-show="cancelModalOpen" x-cloak
            class="fixed inset-0 z-[100000] overflow-hidden p-3 sm:p-6">
            <div class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"
                @click="cancelModalOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">

                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-red-100">
                                <i class="ri-error-warning-line text-lg text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Cancelar pedido</h3>
                                <p class="mt-0.5 text-xs text-gray-400">{{ $saleOrder->number }}</p>
                            </div>
                        </div>
                        <button type="button" @click="cancelModalOpen = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="px-5 py-5 space-y-3">
                        <p class="text-sm text-gray-700">
                            ¿Estás seguro de que deseas cancelar el pedido
                            <strong class="text-gray-900">{{ $saleOrder->number }}</strong>?
                        </p>
                        <div class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-700">
                            <i class="ri-arrow-go-back-line mt-0.5 flex-none"></i>
                            <span>Se restaurará el stock de todos los productos del pedido. Esta acción no se puede deshacer.</span>
                        </div>

                        <div x-show="cancelError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                            x-text="cancelError"></div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                        <button type="button" @click="cancelModalOpen = false"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600">
                            Volver
                        </button>
                        <button type="button" @click="submitCancel()" :disabled="cancelLoading"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-5 text-sm font-bold text-white disabled:opacity-60"
                            style="background:linear-gradient(90deg,#ef4444,#dc2626);">
                            <i class="ri-close-circle-line"></i>
                            <span x-text="cancelLoading ? 'Cancelando...' : 'Sí, cancelar pedido'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: Facturar pedido ══════════════════════════════════════════ --}}
        <div x-show="invoiceModalOpen" x-cloak
            class="fixed inset-0 z-[100000] overflow-hidden p-3 sm:p-6">
            <div class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"
                @click="invoiceModalOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center">
                <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">

                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Facturar pedido</h3>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $saleOrder->number }}</p>
                        </div>
                        <button type="button" @click="invoiceModalOpen = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    {{-- Cuerpo --}}
                    <div class="px-5 py-4 space-y-3">

                        @if ($balance > 0.01)
                            <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
                                <i class="ri-alert-line mt-0.5 flex-none"></i>
                                <span>Saldo pendiente de <strong>S/ {{ number_format($balance, 2) }}</strong>. Registra todos los pagos antes de facturar.</span>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Tipo de documento <span class="text-red-400">*</span></label>
                            <select x-model="invForm.document_type_id"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option value="">Seleccione...</option>
                                @foreach ($documentTypes as $dt)
                                    <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Caja <span class="text-red-400">*</span></label>
                            <select x-model="invForm.cash_register_id"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option value="">Seleccione caja...</option>
                                @foreach ($cashRegisters as $cr)
                                    <option value="{{ $cr->id }}">{{ $cr->number }} ({{ $cr->series }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="invError"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                            x-text="invError"></div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                        <button type="button" @click="invoiceModalOpen = false"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600">
                            Cancelar
                        </button>
                        <button type="button" @click="submitInvoice()" :disabled="invLoading || {{ $balance > 0.01 ? 'true' : 'false' }}"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-5 text-sm font-bold text-white disabled:opacity-50"
                            style="background:linear-gradient(90deg,#3b82f6,#2563eb);">
                            <i class="ri-file-text-line"></i>
                            <span x-text="invLoading ? 'Facturando...' : 'Emitir factura'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: Confirmar Entrega ════════════════════════════════════════ --}}
        <div x-show="deliveryModalOpen" x-cloak
            class="fixed inset-0 z-[100000] overflow-hidden p-3 sm:p-6">
            <div class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"
                @click="deliveryModalOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center">
                <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">

                    {{-- Header --}}
                    <div class="flex items-center gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white" style="background:linear-gradient(135deg,#ff7a00,#ff4d00);">
                            <i class="ri-truck-line text-xl"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-orange-500">Despacho</p>
                            <h3 class="mt-0.5 text-xl font-bold text-gray-900">Confirmar Entrega</h3>
                            <p class="text-sm text-gray-500">Pedido: <span class="font-bold text-orange-600">{{ $saleOrder->number }}</span></p>
                        </div>
                        <button type="button" @click="deliveryModalOpen = false"
                            class="ml-auto flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-500">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="max-h-[75vh] space-y-5 overflow-y-auto p-5 sm:p-6">

                        {{-- Productos --}}
                        <div>
                            <p class="mb-2 text-sm font-bold text-gray-700">Productos de la Venta</p>
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-orange-500">
                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-white">Producto</th>
                                            <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Precio</th>
                                            <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Cant.</th>
                                            <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($saleOrder->items as $item)
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-800">{{ $item->product?->description ?? ($item->product_snapshot['description'] ?? '—') }}</td>
                                                <td class="px-4 py-2.5 text-right text-gray-600">S/ {{ number_format($item->unit_price, 2) }}</td>
                                                <td class="px-4 py-2.5 text-right text-gray-600">{{ (int) $item->quantity }}</td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-gray-700">S/ {{ number_format($item->subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 pr-4 text-right text-sm font-bold text-gray-700">
                                Total Venta: S/ {{ number_format($saleOrder->total, 2) }}
                            </div>
                        </div>

                        {{-- Resumen de pago --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">Resumen de Pago</p>
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Ya pagado:</span>
                                    <span class="font-semibold text-emerald-600">S/ {{ number_format($saleOrder->paid, 2) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1.5 text-sm">
                                    <span class="font-semibold {{ $balance > 0.01 ? 'text-orange-600' : 'text-gray-600' }}">Saldo pendiente:</span>
                                    <span class="font-bold {{ $balance > 0.01 ? 'text-orange-600' : 'text-emerald-600' }}">S/ {{ number_format($balance, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Detalles del despacho --}}
                        <div class="space-y-4">
                            <p class="text-sm font-bold text-gray-700">Detalles del Despacho</p>

                            {{-- Número de guía --}}
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Número de Guía</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="ri-file-list-3-line"></i></span>
                                    <input type="text" x-model="deliveryTrackingNumber" placeholder="Ej: GR-00001"
                                        class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                                </div>
                            </div>

                            {{-- Foto de evidencia --}}
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Foto de Evidencia</label>
                                <input type="file" id="so-delivery-photo" accept="image/*" class="hidden">
                                <input type="file" id="so-delivery-camera" accept="image/*" capture="environment" class="hidden">
                                <div class="flex gap-2">
                                    <button type="button" onclick="document.getElementById('so-delivery-camera').click()"
                                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-orange-400 hover:text-orange-600">
                                        <i class="ri-camera-line text-base"></i> Tomar foto
                                    </button>
                                    <button type="button" onclick="document.getElementById('so-delivery-photo').click()"
                                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-orange-400 hover:text-orange-600">
                                        <i class="ri-image-line text-base"></i> Galería
                                    </button>
                                </div>
                                <p id="so-delivery-filename" class="mt-1.5 text-xs text-gray-400">Ningún archivo seleccionado</p>
                            </div>

                            {{-- ¿Se registró el pago? (solo si hay saldo pendiente) --}}
                            @if($balance > 0.01)
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">¿Se registró el pago?</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="deliveryPaymentConfirmed = 1"
                                        :class="deliveryPaymentConfirmed === 1 ? 'border-emerald-400 bg-emerald-100 text-emerald-800 ring-2 ring-emerald-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-all">
                                        <i class="ri-check-line text-base"></i> Sí, pagado
                                    </button>
                                    <button type="button" @click="deliveryPaymentConfirmed = 0"
                                        :class="deliveryPaymentConfirmed === 0 ? 'border-rose-400 bg-rose-100 text-rose-800 ring-2 ring-rose-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-all">
                                        <i class="ri-time-line text-base"></i> Pendiente
                                    </button>
                                </div>
                            </div>

                            {{-- Método de pago (si marcó sí, pagado) --}}
                            <div x-show="deliveryPaymentConfirmed === 1"
                                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                 class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                                <div class="flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Método de Pago <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="ri-bank-card-line"></i></span>
                                            <select x-model="deliveryPaymentMethodId"
                                                class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                                                <option value="">Seleccionar método...</option>
                                                @foreach($paymentMethods as $pm)
                                                    <option value="{{ $pm->id }}">{{ $pm->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="w-32 flex-none">
                                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Monto</label>
                                        <div class="flex overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100">
                                            <span class="flex h-11 items-center border-r border-gray-200 bg-gray-100 px-2.5 text-xs font-bold text-gray-500">S/</span>
                                            <input type="number" x-model="deliveryPaymentAmount" min="0.01" step="0.01"
                                                class="h-11 w-full bg-transparent px-2.5 text-sm font-semibold text-gray-800 outline-none">
                                        </div>
                                    </div>
                                </div>
                                <div x-show="deliverySelectedMethodKind === 'wallet' && digitalWallets.length > 0" x-transition>
                                    <select x-model="deliveryDigitalWalletId"
                                        class="h-10 w-full rounded-xl border border-violet-200 bg-violet-50 px-3 text-sm font-medium text-violet-800 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                                        <option value="">Selecciona la billetera...</option>
                                        @foreach($digitalWallets as $dw)
                                            <option value="{{ $dw->id }}">{{ $dw->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="deliverySelectedMethodKind === 'card' && cards.length > 0" x-transition>
                                    <select x-model="deliveryCardId"
                                        class="h-10 w-full rounded-xl border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                                        <option value="">Selecciona la tarjeta...</option>
                                        @foreach($cards as $c)
                                            <option value="{{ $c->id }}">{{ $c->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4">
                        <button type="button" @click="deliveryModalOpen = false"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                            <i class="ri-close-line"></i> Cancelar
                        </button>
                        <button type="button" @click="submitDelivery()" :disabled="deliveryProcessing"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-6 text-sm font-bold text-white shadow-sm disabled:opacity-60"
                            style="background:linear-gradient(90deg,#ff7a00,#ff4d00);">
                            <i class="ri-check-double-line"></i>
                            <span x-text="deliveryProcessing ? 'Guardando...' : 'Confirmar Entrega'"></span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script>
    function soShowPage() {
        return {
            // Payment modal
            paymentModalOpen: false,
            payLoading: false,
            payError: '',
            payForm: { paid_at: '', notes: '', cash_register_id: @json($defaultCashRegisterId ?? null), generate_invoice: true, methods: [{ payment_method_id: '', amount: '', reference: '', card_id: '', digital_wallet_id: '' }] },
            payMethodTypes: @json($payMethodTypes),
            defaultCashRegisterId: @json($defaultCashRegisterId ?? null),

            get totalDistributed() {
                return this.payForm.methods.reduce((sum, m) => sum + (parseFloat(m.amount) || 0), 0);
            },

            // Return modal
            returnModalOpen: false,
            retLoading: false,
            retError: '',
            retForm: { sale_order_item_id: '', quantity: '', notes: '' },
            returnableQtys: @json(collect($returnablePerId)->mapWithKeys(fn($v, $k) => [(string)$k => (float)$v])),

            // Invoice modal
            invoiceModalOpen: false,
            invLoading: false,
            invError: '',
            invForm: { document_type_id: '', cash_register_id: '' },

            // Delivery revert (PENDIENTE)
            deliveryLoading: false,

            async markDelivery(status) {
                this.deliveryLoading = true;
                try {
                    const res  = await fetch(@json(route('admin.sale-orders.delivery.update', $saleOrder)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ delivery_status: status }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al actualizar entrega.');
                    window.location.reload();
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.deliveryLoading = false;
                }
            },

            // Delivery confirm modal
            deliveryModalOpen: false,
            deliveryProcessing: false,
            deliveryTrackingNumber: '',
            deliveryPaymentConfirmed: null,
            deliveryPaymentMethodId: '',
            deliveryPaymentAmount: '{{ number_format($balance, 2, '.', '') }}',
            deliveryDigitalWalletId: '',
            deliveryCardId: '',
            deliveryCashRegisterId: '{{ $defaultCashRegisterId ?? '' }}',
            deliveryBalance: {{ $balance }},
            paymentMethods: @json($paymentMethods->map(fn($m) => ['id' => $m->id, 'description' => $m->description])),
            digitalWallets: @json($digitalWallets->map(fn($m) => ['id' => $m->id])),
            cards: @json($cards->map(fn($m) => ['id' => $m->id])),

            get deliverySelectedMethodKind() {
                const pm = (this.paymentMethods || []).find(m => String(m.id) === String(this.deliveryPaymentMethodId));
                if (!pm) return 'plain';
                const d = (pm.description || '').toLowerCase();
                if (d.includes('tarjeta') || d.includes('card')) return 'card';
                if (d.includes('billetera') || d.includes('wallet') || d.includes('digital')) return 'wallet';
                return 'plain';
            },

            openDeliveryConfirmModal() {
                this.deliveryModalOpen = true;
                this.deliveryTrackingNumber = '';
                this.deliveryPaymentConfirmed = null;
                this.deliveryPaymentMethodId = '';
                this.deliveryPaymentAmount = parseFloat({{ $balance }}).toFixed(2);
                this.deliveryDigitalWalletId = '';
                this.deliveryCardId = '';
                ['so-delivery-camera', 'so-delivery-photo'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                const fn = document.getElementById('so-delivery-filename');
                if (fn) fn.textContent = 'Ningún archivo seleccionado';
            },

            async submitDelivery() {
                const needsPayment = this.deliveryPaymentConfirmed === 1 && this.deliveryBalance > 0.01;
                if (this.deliveryBalance > 0.01 && this.deliveryPaymentConfirmed === null) {
                    alert('Por favor indica si se registró el pago.');
                    return;
                }
                if (needsPayment && !this.deliveryPaymentMethodId) {
                    alert('Selecciona el método de pago.');
                    return;
                }
                this.deliveryProcessing = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const formData = new FormData();
                    formData.append('payment_confirmed', this.deliveryPaymentConfirmed === 1 ? '1' : '0');
                    if (this.deliveryTrackingNumber.trim()) formData.append('tracking_number', this.deliveryTrackingNumber.trim());
                    if (needsPayment) {
                        formData.append('payment_method_id', this.deliveryPaymentMethodId);
                        formData.append('payment_amount', parseFloat(this.deliveryPaymentAmount).toFixed(2));
                        if (this.deliveryCashRegisterId) formData.append('cash_register_id', this.deliveryCashRegisterId);
                        if (this.deliverySelectedMethodKind === 'wallet' && this.deliveryDigitalWalletId) formData.append('digital_wallet_id', this.deliveryDigitalWalletId);
                        if (this.deliverySelectedMethodKind === 'card' && this.deliveryCardId) formData.append('card_id', this.deliveryCardId);
                    }
                    const cameraInput = document.getElementById('so-delivery-camera');
                    const photoInput  = document.getElementById('so-delivery-photo');
                    const evidenceFile = cameraInput?.files[0] || photoInput?.files[0];
                    if (evidenceFile) formData.append('evidence_photo', evidenceFile);

                    const res = await fetch(@json(route('admin.dispatch.deliver', $saleOrder)), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        body: formData,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al confirmar entrega.');
                    window.location.reload();
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.deliveryProcessing = false;
                }
            },

            // Cancel modal
            cancelModalOpen: false,
            cancelLoading: false,
            cancelError: '',

            openPaymentModal() {
                this.payError = '';
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const localNow = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
                this.payForm = {
                    paid_at: localNow,
                    notes: '',
                    cash_register_id: this.defaultCashRegisterId,
                    generate_invoice: true,
                    methods: [{ payment_method_id: '', amount: '', reference: '', card_id: '', digital_wallet_id: '' }],
                };
                this.paymentModalOpen = true;
            },

            addPayMethod() {
                if (this.payForm.methods.length < 4) {
                    this.payForm.methods.push({ payment_method_id: '', amount: '', reference: '', card_id: '', digital_wallet_id: '' });
                }
            },

            onMethodChange(method) {
                method.card_id = '';
                method.digital_wallet_id = '';
            },

            removePayMethod(index) {
                this.payForm.methods.splice(index, 1);
            },
            openReturnModal() {
                this.retError = '';
                this.retForm  = { sale_order_item_id: '', quantity: '', notes: '' };
                this.returnModalOpen = true;
            },
            openInvoiceModal() {
                this.invError = '';
                this.invForm  = { document_type_id: '', cash_register_id: '' };
                this.invoiceModalOpen = true;
            },

            async submitPayment() {
                this.payError = '';
                if (!this.payForm.cash_register_id) {
                    this.payError = 'Selecciona la caja donde se registrará el pago.'; return;
                }
                for (const m of this.payForm.methods) {
                    if (!m.payment_method_id) { this.payError = 'Selecciona el método de pago en cada fila.'; return; }
                    if (!m.amount || parseFloat(m.amount) < 1) { this.payError = 'El monto mínimo por método es S/ 1.00.'; return; }
                    const t = this.payMethodTypes[m.payment_method_id];
                    if (t === 'card' && !m.card_id) { this.payError = 'Selecciona la tarjeta para el método correspondiente.'; return; }
                    if (t === 'wallet' && !m.digital_wallet_id) { this.payError = 'Selecciona la billetera digital.'; return; }
                }
                const balance = {{ $balance }};
                if (this.totalDistributed > balance + 0.001) {
                    this.payError = `El total (S/ ${this.totalDistributed.toFixed(2)}) supera el saldo pendiente (S/ ${balance.toFixed(2)}). Ajusta los montos.`;
                    return;
                }
                this.payLoading = true;
                try {
                    const res  = await fetch(@json(route('admin.sale-orders.payments.store', $saleOrder)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(this.payForm),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al registrar pago.');
                    this.paymentModalOpen = false;
                    window.location.reload();
                } catch (e) {
                    this.payError = e.message;
                } finally {
                    this.payLoading = false;
                }
            },

            async submitReturn() {
                this.retError = '';
                if (!this.retForm.sale_order_item_id) {
                    this.retError = 'Selecciona el producto.'; return;
                }
                const qty    = parseFloat(this.retForm.quantity);
                const maxQty = this.returnableQtys[this.retForm.sale_order_item_id] ?? 0;
                if (!qty || qty <= 0) {
                    this.retError = 'Ingresa una cantidad válida.'; return;
                }
                if (qty > maxQty + 0.000001) {
                    this.retError = `La cantidad supera el máximo devolvible (${maxQty.toFixed(2)}) según los pagos registrados.`; return;
                }
                this.retLoading = true;
                try {
                    const res  = await fetch(@json(route('admin.sale-orders.returns.store', $saleOrder)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(this.retForm),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al registrar devolución.');
                    this.returnModalOpen = false;
                    window.location.reload();
                } catch (e) {
                    this.retError = e.message;
                } finally {
                    this.retLoading = false;
                }
            },

            async submitInvoice() {
                this.invError = '';
                if (!this.invForm.document_type_id) {
                    this.invError = 'Selecciona el tipo de documento.'; return;
                }
                if (!this.invForm.cash_register_id) {
                    this.invError = 'Selecciona la caja.'; return;
                }
                this.invLoading = true;
                try {
                    const res  = await fetch(@json(route('admin.sale-orders.invoice', $saleOrder)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(this.invForm),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al facturar.');
                    this.invoiceModalOpen = false;
                    window.location.reload();
                } catch (e) {
                    this.invError = e.message;
                } finally {
                    this.invLoading = false;
                }
            },

            confirmCancel() {
                this.cancelError = '';
                this.cancelModalOpen = true;
            },

            async submitCancel() {
                this.cancelError = '';
                this.cancelLoading = true;
                try {
                    const res  = await fetch(@json(route('admin.sale-orders.cancel', $saleOrder)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error al cancelar.');
                    this.cancelModalOpen = false;
                    window.location.reload();
                } catch (e) {
                    this.cancelError = e.message;
                } finally {
                    this.cancelLoading = false;
                }
            },

            init() {
                ['so-delivery-photo', 'so-delivery-camera'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('change', () => {
                        const fn = document.getElementById('so-delivery-filename');
                        if (fn) fn.textContent = el.files[0]?.name || 'Ningún archivo seleccionado';
                    });
                });
            },
        };
    }
    </script>
@endsection
