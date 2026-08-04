@extends('layouts.app')

@section('content')
    <div id="dispatch-view">
        <x-common.page-breadcrumb
            pageTitle="Despacho"
            :crumbs="[['label' => 'Despacho']]"
        >
            <button type="button" id="quick-dispatch-btn" onclick="openQuickDispatchModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition-colors">
                <i class="ri-add-line text-base"></i>
                Despacho Rápido
            </button>
        </x-common.page-breadcrumb>

        <x-common.component-card title="Pedidos de Entrega" desc="Pedidos con envío asignados a esta sucursal.">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('admin.dispatch.index') }}" class="mb-5 flex flex-col gap-3">

                {{-- Fila 1: búsqueda + fechas --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

                    <div class="relative flex-1 max-w-sm">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por N° pedido o cliente..."
                            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-100">
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Desde</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-100">
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Hasta</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-100">
                    </div>

                    <input type="hidden" name="status" value="{{ $status }}">

                    <button type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="ri-search-line"></i> Buscar
                    </button>

                    @if ($search || $dateFrom || $dateTo)
                        <a href="{{ route('admin.dispatch.index', ['status' => $status]) }}"
                            class="inline-flex h-10 items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-3 text-sm font-semibold text-rose-600 hover:bg-rose-100">
                            <i class="ri-close-line"></i> Limpiar
                        </a>
                    @endif
                </div>

                {{-- Fila 2: tabs de estado --}}
                <div class="flex items-center gap-2">
                    @php
                        $tabs = [
                            'all'       => ['label' => 'Todos',          'icon' => 'ri-list-check'],
                            'pending'   => ['label' => 'Por Entregar',   'icon' => 'ri-truck-line'],
                            'delivered' => ['label' => 'Entregados',     'icon' => 'ri-checkbox-circle-line'],
                        ];
                    @endphp
                    @foreach ($tabs as $tabKey => $tab)
                        <a href="{{ route('admin.dispatch.index', array_filter(['status' => $tabKey, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl px-3.5 text-xs font-bold transition
                                {{ $status === $tabKey
                                    ? 'bg-orange-500 text-white shadow-sm'
                                    : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                            <i class="{{ $tab['icon'] }} text-sm"></i>
                            {{ $tab['label'] }}
                        </a>
                    @endforeach

                    <span class="ml-auto text-sm text-slate-400">
                        {{ $orders->total() }} resultado(s)
                    </span>
                </div>

            </form>

            {{-- Tabla --}}
            @if ($orders->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <i class="ri-truck-line text-2xl text-emerald-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No hay pedidos para mostrar</p>
                    <p class="mt-1 text-xs text-slate-400">Prueba cambiando los filtros.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left">
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Pedido</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Cliente</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Total</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Pago</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Fecha pedido</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Entrega</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($orders as $order)
                                @php
                                    $isPending = in_array($order->delivery?->status, ['EN_PROCESO', 'PENDIENTE']);
                                    $itemsJson = $order->items->map(fn ($i) => [
                                        'name'     => data_get($i->product_snapshot, 'description', 'Producto'),
                                        'qty'      => (float) $i->quantity,
                                        'price'    => (float) $i->unit_price,
                                        'subtotal' => (float) $i->subtotal,
                                    ]);
                                @endphp
                                <tr id="dispatch-row-{{ $order->id }}"
                                    class="transition {{ $isPending ? 'hover:bg-slate-50/60' : 'bg-slate-50/40 hover:bg-slate-50' }}">

                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.sale-orders.show', $order) }}"
                                            class="font-bold text-orange-600 hover:underline">
                                            {{ $order->number }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3 font-medium text-slate-800">
                                        {{ $order->person_name ?? 'Público general' }}
                                    </td>

                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        S/ {{ number_format($order->total, 2) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @php
                                            $payStatusMap = [
                                                'draft'     => ['label' => 'Pago Pendiente', 'icon' => 'ri-time-line',           'class' => 'bg-amber-100 text-amber-700'],
                                                'partial'   => ['label' => 'Pago Parcial',   'icon' => 'ri-pie-chart-line',       'class' => 'bg-blue-100 text-blue-700'],
                                                'completed' => ['label' => 'Completado',      'icon' => 'ri-checkbox-circle-fill', 'class' => 'bg-emerald-100 text-emerald-700'],
                                                'cancelled' => ['label' => 'Cancelado',       'icon' => 'ri-close-circle-fill',    'class' => 'bg-rose-100 text-rose-600'],
                                            ];
                                            $ps = $payStatusMap[$order->status] ?? ['label' => $order->status, 'icon' => 'ri-question-line', 'class' => 'bg-slate-100 text-slate-500'];
                                        @endphp
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $ps['class'] }}">
                                            <i class="{{ $ps['icon'] }} text-xs"></i>
                                            {{ $ps['label'] }}
                                        </span>
                                        @if ($order->status === 'partial')
                                            @php $pendingBalance = (float)$order->total - (float)$order->paid; @endphp
                                            <p class="mt-0.5 text-[10px] text-slate-400">Saldo: S/ {{ number_format($pendingBalance, 2) }}</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-slate-500">
                                        {{ $order->created_at->format('d/m/Y H:i') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($isPending)
                                            <span id="delivery-badge-{{ $order->id }}"
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                <i class="ri-truck-line text-xs"></i> Por Entregar
                                            </span>
                                        @else
                                            <span id="delivery-badge-{{ $order->id }}"
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                                <i class="ri-checkbox-circle-fill text-xs"></i> Entregado
                                            </span>
                                            @if ($order->delivery?->delivered_at)
                                                <p class="mt-0.5 text-[10px] text-slate-400">
                                                    {{ $order->delivery->delivered_at->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-4 py-3" id="dispatch-action-{{ $order->id }}">
                                        @if ($isPending)
                                            <button
                                                type="button"
                                                id="deliver-btn-{{ $order->id }}"
                                                onclick="dispatchDeliverModal({{ $order->id }}, '{{ $order->number }}', {{ (float)$order->total }}, {{ json_encode($itemsJson) }}, {{ (float)$order->paid }})"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                                                <i class="ri-checkbox-circle-line text-sm"></i>
                                                Marcar entregado
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="mt-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif

        </x-common.component-card>
    </div>

    {{-- Modal Confirmar Entrega --}}
    <x-ui.modal
        x-data="deliverModalData()"
        @open-deliver-modal.window="openModal($event.detail)"
        :showCloseButton="false"
        class="max-w-md">

        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-900">

            {{-- Header --}}
            <div class="relative border-b border-gray-100 p-6 dark:border-gray-800 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg shadow-orange-500/20">
                            <i class="ri-truck-line text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-orange-500">Despacho</p>
                            <h3 class="mt-0.5 text-xl font-bold text-gray-900 dark:text-white">Confirmar Entrega</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Pedido: <span x-text="orderNumber" class="font-bold text-orange-600">—</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-all hover:bg-red-50 hover:text-red-500 dark:bg-gray-800 dark:hover:bg-red-900/30">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="space-y-6 p-6 sm:p-8">

                {{-- Productos del pedido --}}
                <div>
                    <p class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-200">Productos de la Venta</p>
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-orange-500 dark:border-gray-700">
                                    <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-white">Producto</th>
                                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Precio</th>
                                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Cant.</th>
                                    <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-wider text-white">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                <template x-for="item in items" :key="item.name">
                                    <tr>
                                        <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200" x-text="item.name"></td>
                                        <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-400" x-text="'S/ ' + item.price.toFixed(2)"></td>
                                        <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-400" x-text="parseFloat(item.qty).toFixed(0)"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-gray-700 dark:text-gray-300" x-text="'S/ ' + item.subtotal.toFixed(2)"></td>
                                    </tr>
                                </template>
                                <template x-if="items.length === 0">
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-xs text-gray-400">Sin productos</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 pr-4 text-right text-sm font-bold text-gray-700 dark:text-gray-200">
                        Total Venta: S/ <span x-text="total">0.00</span>
                    </div>
                </div>

                {{-- Resumen de Pago --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Resumen de Pago</p>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Ya pagado:</span>
                            <span x-text="'S/ ' + parseFloat(paid).toFixed(2)" class="font-semibold text-emerald-600"></span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-1.5 text-sm dark:border-gray-700">
                            <span class="font-semibold" :class="parseFloat(balance) > 0.01 ? 'text-orange-600' : 'text-gray-600 dark:text-gray-400'">Saldo pendiente:</span>
                            <span x-text="'S/ ' + parseFloat(balance).toFixed(2)" class="font-bold" :class="parseFloat(balance) > 0.01 ? 'text-orange-600' : 'text-emerald-600'"></span>
                        </div>
                    </div>
                </div>

                {{-- Detalles del Despacho --}}
                <div class="space-y-5">
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Detalles del Despacho</p>

                    {{-- Número de Guía --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Número de Guía
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-file-list-3-line"></i>
                            </span>
                            <input type="text" id="modal-tracking-number" x-model="trackingNumber"
                                placeholder="Ej: GR-00001"
                                class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>

                    {{-- Foto de Evidencia --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Foto de Evidencia
                        </label>
                        <input type="file" id="modal-evidence-photo" accept="image/*" class="hidden">
                        <input type="file" id="modal-evidence-camera" accept="image/*" capture="environment" class="hidden">
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('modal-evidence-camera').click()"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-orange-400 hover:text-orange-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <i class="ri-camera-line text-base"></i> Tomar foto
                            </button>
                            <button type="button" onclick="document.getElementById('modal-evidence-photo').click()"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-orange-400 hover:text-orange-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <i class="ri-image-line text-base"></i> Galería
                            </button>
                        </div>
                        <p id="modal-evidence-filename" class="mt-1.5 text-xs text-gray-400">Ningún archivo seleccionado</p>
                    </div>

                    {{-- ¿Se registró el pago? --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            ¿Se registró el pago?
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="paymentConfirmed = 1"
                                :class="paymentConfirmed === 1
                                    ? 'border-emerald-400 bg-emerald-100 text-emerald-800 ring-2 ring-emerald-300'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-all">
                                <i class="ri-check-line text-base"></i> Si, pagado
                            </button>
                            <button type="button" @click="paymentConfirmed = 0"
                                :class="paymentConfirmed === 0
                                    ? 'border-rose-400 bg-rose-100 text-rose-800 ring-2 ring-rose-300'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-all">
                                <i class="ri-time-line text-base"></i> Pendiente
                            </button>
                        </div>
                    </div>

                    {{-- Método de pago y caja (solo cuando hay saldo y marcó como pagado) --}}
                    <div x-show="paymentConfirmed === 1 && parseFloat(balance) > 0.01"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-800 dark:bg-emerald-900/10">

                        {{-- Método de pago + Monto --}}
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Método de Pago <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                        <i class="ri-bank-card-line"></i>
                                    </span>
                                    <select x-model="paymentMethodId"
                                        class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <option value="">Seleccionar método...</option>
                                        <template x-for="pm in paymentMethods" :key="pm.id">
                                            <option :value="pm.id" x-text="pm.description"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="w-32 flex-none">
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Monto</label>
                                <div class="flex overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100">
                                    <span class="flex h-11 items-center border-r border-gray-200 bg-gray-100 px-2.5 text-xs font-bold text-gray-500">S/</span>
                                    <input type="number" x-model="paymentAmount" min="0.01" step="0.01"
                                        class="h-11 w-full bg-transparent px-2.5 text-sm font-semibold text-gray-800 outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- Aviso pago parcial --}}
                        <div x-show="parseFloat(paymentAmount) > 0 && parseFloat(paymentAmount) < parseFloat(balance) - 0.001"
                             x-transition
                             class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                            <i class="ri-information-line flex-none"></i>
                            <span>Pago parcial — el pedido quedará con saldo pendiente de
                                <strong x-text="'S/ ' + (parseFloat(balance) - parseFloat(paymentAmount || 0)).toFixed(2)"></strong>
                            </span>
                        </div>

                        {{-- Sub-select: Billetera Virtual (violeta) --}}
                        <div x-show="selectedMethodKind === 'wallet' && digitalWallets.length > 0" x-transition>
                            <select x-model="digitalWalletId"
                                class="h-10 w-full rounded-xl border border-violet-200 bg-violet-50 px-3 text-sm font-medium text-violet-800 outline-none focus:border-violet-400 focus:bg-white focus:ring-2 focus:ring-violet-100">
                                <option value="">Selecciona la billetera...</option>
                                <template x-for="dw in digitalWallets" :key="dw.id">
                                    <option :value="dw.id" x-text="dw.description"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Sub-select: Tarjeta (azul) --}}
                        <div x-show="selectedMethodKind === 'card' && cards.length > 0" x-transition>
                            <select x-model="cardId"
                                class="h-10 w-full rounded-xl border border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-800 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option value="">Selecciona la tarjeta...</option>
                                <template x-for="c in cards" :key="c.id">
                                    <option :value="c.id" x-text="c.description + (c.type === 'C' ? ' (Crédito)' : c.type === 'D' ? ' (Débito)' : '')"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Caja: auto-seleccionada, solo lectura --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Caja
                            </label>
                            <template x-if="autoCashRegister">
                                <div class="flex h-11 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                                    <i class="ri-safe-line text-emerald-600"></i>
                                    <span x-text="'Caja ' + autoCashRegister.number"></span>
                                    <span class="ml-auto rounded-full bg-emerald-200 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">Activa</span>
                                </div>
                            </template>
                            <template x-if="!autoCashRegister">
                                <div class="flex h-11 items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 text-sm font-semibold text-rose-700 dark:border-rose-800 dark:bg-rose-900/20">
                                    <i class="ri-error-warning-line text-rose-600"></i>
                                    <span>Sin caja abierta — no se puede registrar el cobro</span>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 px-6 py-5 dark:border-gray-800 sm:px-8">
                <button type="button" @click="open = false"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <i class="ri-close-line"></i>
                    <span>Cancelar</span>
                </button>
                <button type="button" @click="submitModal()"
                    :disabled="processing"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-orange-500 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600 active:scale-95 disabled:opacity-60">
                    <i class="ri-checkbox-circle-line" x-show="!processing"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="processing" x-cloak></i>
                    <span x-text="processing ? 'Procesando...' : 'Confirmar Entrega'">Confirmar Entrega</span>
                </button>
            </div>

        </div>
    </x-ui.modal>

    {{-- Modal: Despacho Rápido (venta directa) — estructura visual, pendiente de conectar --}}
    <x-ui.modal
        x-data="quickDispatchModalData()"
        @open-quick-dispatch-modal.window="openModal()"
        :showCloseButton="false"
        class="max-w-4xl">

        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-900">

            {{-- Header --}}
            <div class="relative border-b border-gray-100 p-6 dark:border-gray-800 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg shadow-orange-500/20">
                            <i class="ri-flashlight-line text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-orange-500">Despacho</p>
                            <h3 class="mt-0.5 text-xl font-bold text-gray-900 dark:text-white">Despacho Rápido</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Registra una venta directa en segundos</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-all hover:bg-red-50 hover:text-red-500 dark:bg-gray-800 dark:hover:bg-red-900/30">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="grid gap-0 sm:grid-cols-5">

                {{-- Columna izquierda: productos --}}
                <div class="space-y-5 border-b border-gray-100 p-6 dark:border-gray-800 sm:col-span-3 sm:border-b-0 sm:border-r sm:p-8">

                    {{-- Buscador de productos --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Producto <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="text" x-model="productSearch" @focus="productOpen = true"
                                @click.outside="productOpen = false"
                                placeholder="Buscar por nombre o código..."
                                autocomplete="off"
                                class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

                            {{-- Resultados --}}
                            <div x-show="productOpen && filteredProducts.length"
                                x-transition
                                class="absolute z-20 mt-1.5 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                <template x-for="p in filteredProducts" :key="p.id">
                                    <button type="button" @click="addProduct(p)"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left text-sm hover:bg-orange-50 dark:hover:bg-gray-700">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="p.name"></span>
                                        <span class="text-xs font-bold text-orange-600" x-text="'S/ ' + p.price.toFixed(2)"></span>
                                    </button>
                                </template>
                            </div>
                            <div x-show="productOpen && productSearch && !filteredProducts.length"
                                class="absolute z-20 mt-1.5 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-400 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                Sin resultados
                            </div>
                        </div>
                    </div>

                    {{-- Carrito --}}
                    <div>
                        <p class="mb-2 text-sm font-bold text-gray-700 dark:text-gray-200">Productos agregados</p>
                        <div class="max-h-[280px] space-y-2 overflow-y-auto pr-1">
                            <template x-for="item in cartItems" :key="item.product_id">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <p class="text-sm font-bold leading-tight text-gray-900 dark:text-gray-100" x-text="item.name"></p>
                                        <button type="button" @click="removeProduct(item.product_id)"
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500">
                                            <i class="ri-close-line text-sm"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-1 dark:border-gray-700 dark:bg-gray-900" style="height:34px;">
                                            <button type="button" @click="updateQty(item.product_id, -1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <i class="ri-subtract-line text-sm"></i>
                                            </button>
                                            <input type="number"
                                                min="1"
                                                step="1"
                                                x-model.number="item.quantity"
                                                @input="if (item.quantity !== '' && item.quantity < 1) item.quantity = 1"
                                                @blur="if (!item.quantity || item.quantity < 1) item.quantity = 1"
                                                class="w-10 text-center text-sm font-bold text-gray-900 focus:outline-none dark:text-gray-100 bg-transparent border-0 p-0 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                            <button type="button" @click="updateQty(item.product_id, 1)"
                                                class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <i class="ri-add-line text-sm"></i>
                                            </button>
                                        </div>
                                        <span class="text-xs text-gray-400">S/ <span x-text="item.unit_price.toFixed(2)"></span> c/u</span>
                                        <span class="text-sm font-bold text-orange-600" x-text="'S/ ' + ((parseFloat(item.quantity) || 0) * item.unit_price).toFixed(2)"></span>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!cartItems.length" class="py-8 text-center text-sm text-gray-400">
                                <i class="ri-shopping-cart-line text-2xl"></i><br>Busca y agrega productos
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Columna derecha: cliente + total --}}
                <div class="space-y-5 p-6 dark:border-gray-800 sm:col-span-2 sm:p-8">

                    {{-- Cliente --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Cliente
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-user-line"></i>
                            </span>
                            <input type="text" x-model="clientSearch" @focus="clientOpen = true"
                                @click.outside="clientOpen = false"
                                placeholder="Cliente varios (opcional)"
                                autocomplete="off"
                                class="h-11 w-full rounded-xl border border-gray-300 bg-white pl-9 pr-4 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

                            <div x-show="clientOpen && filteredClients.length"
                                x-transition
                                class="absolute z-20 mt-1.5 max-h-48 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                <template x-for="c in filteredClients" :key="c.id">
                                    <button type="button" @click="selectClient(c)"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left text-sm hover:bg-orange-50 dark:hover:bg-gray-700">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="c.name"></span>
                                        <span class="text-xs text-gray-400" x-text="c.document"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Métodos de Pago --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Método(s) de Pago <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="addPaymentRow()"
                                class="inline-flex items-center gap-1 rounded-lg bg-orange-50 px-2 py-1 text-xs font-bold text-orange-600 transition hover:bg-orange-100 dark:bg-orange-950/40 dark:text-orange-400">
                                <i class="ri-add-line text-sm"></i>
                                <span>+ Agregar pago</span>
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <template x-for="(pay, index) in payments" :key="index">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 space-y-2 dark:border-gray-700 dark:bg-gray-800/40">
                                    <div class="flex items-center gap-2">
                                        {{-- Método --}}
                                        <div class="flex-1">
                                            <select x-model="pay.payment_method_id"
                                                class="h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                                <option value="">Seleccionar método...</option>
                                                <template x-for="pm in paymentMethods" :key="pm.id">
                                                    <option :value="pm.id" x-text="pm.description"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- Monto --}}
                                        <div class="w-28 flex-none">
                                            <div class="flex overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-orange-400 dark:border-gray-700 dark:bg-gray-900">
                                                <span class="flex h-10 items-center border-r border-gray-200 bg-gray-100 px-2 text-[11px] font-bold text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">S/</span>
                                                <input type="number" min="0.01" step="0.01" x-model.number="pay.amount"
                                                    class="h-10 w-full bg-transparent px-2 text-xs font-bold text-gray-900 outline-none dark:text-white">
                                            </div>
                                        </div>

                                        {{-- Botón eliminar si hay más de 1 pago --}}
                                        <template x-if="payments.length > 1">
                                            <button type="button" @click="removePaymentRow(index)"
                                                class="flex h-10 w-8 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-950/40">
                                                <i class="ri-delete-bin-line text-base"></i>
                                            </button>
                                        </template>
                                    </div>

                                    {{-- Sub-select: Billetera Virtual --}}
                                    <div x-show="getPaymentMethodKind(pay.payment_method_id) === 'wallet' && digitalWallets.length > 0" x-transition>
                                        <select x-model="pay.digital_wallet_id"
                                            class="h-9 w-full rounded-xl border border-violet-200 bg-violet-50 px-3 text-xs font-medium text-violet-800 outline-none focus:border-violet-400 focus:bg-white dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-300">
                                            <option value="">Selecciona billetera...</option>
                                            <template x-for="dw in digitalWallets" :key="dw.id">
                                                <option :value="dw.id" x-text="dw.description"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Sub-select: Tarjeta --}}
                                    <div x-show="getPaymentMethodKind(pay.payment_method_id) === 'card' && cards.length > 0" x-transition>
                                        <select x-model="pay.card_id"
                                            class="h-9 w-full rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-medium text-blue-800 outline-none focus:border-blue-400 focus:bg-white dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300">
                                            <option value="">Selecciona tarjeta...</option>
                                            <template x-for="c in cards" :key="c.id">
                                                <option :value="c.id" x-text="c.description + (c.type === 'C' ? ' (Crédito)' : c.type === 'D' ? ' (Débito)' : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Desglose total pagado cuando hay múltiples métodos --}}
                        <template x-if="payments.length > 1">
                            <div class="mt-1.5 flex items-center justify-between text-xs font-semibold">
                                <span class="text-gray-500">Total pagado:</span>
                                <span :class="Math.abs(totalPaid - total) < 0.01 ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'"
                                    x-text="'S/ ' + totalPaid.toFixed(2) + ' de S/ ' + total.toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Indicador de Caja Activa --}}
                    <div>
                        <template x-if="autoCashRegister">
                            <div class="flex h-9 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                                <i class="ri-safe-line text-emerald-600"></i>
                                <span x-text="'Caja ' + autoCashRegister.number"></span>
                                <span class="ml-auto rounded-full bg-emerald-200 px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">Activa</span>
                            </div>
                        </template>
                        <template x-if="!autoCashRegister">
                            <div class="flex h-9 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/20">
                                <i class="ri-error-warning-line text-amber-600"></i>
                                <span>Sin caja abierta</span>
                            </div>
                        </template>
                    </div>

                    {{-- Total --}}
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total a cobrar</p>
                        <p class="text-3xl font-black text-orange-600" x-text="'S/ ' + total.toFixed(2)"></p>
                        <p class="mt-1 text-xs text-gray-400" x-text="cartItems.length + ' producto(s)'"></p>
                    </div>


                </div>
            </div>

            {{-- Footer --}}
            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 px-6 py-5 dark:border-gray-800 sm:px-8">
                <button type="button" @click="open = false"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <i class="ri-close-line"></i>
                    <span>Cancelar</span>
                </button>
                <button type="button" @click="submitQuickSale()" :disabled="!cartItems.length || processing"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-orange-500 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600 active:scale-95 disabled:opacity-60">
                    <i class="ri-flashlight-line" x-show="!processing"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="processing" x-cloak></i>
                    <span x-text="processing ? 'Procesando...' : 'Registrar venta'">Registrar venta</span>
                </button>
            </div>

        </div>
    </x-ui.modal>

    {{-- Toast --}}
    <div id="dispatch-toast"
        class="pointer-events-none fixed right-6 top-24 z-50 translate-x-[140%] opacity-0 transition-all duration-300">
        <div class="flex min-w-[300px] items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-4 shadow-2xl">
            <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                <i class="ri-check-line text-lg"></i>
            </div>
            <div class="flex-1">
                <p id="dispatch-toast-msg" class="text-sm font-semibold text-slate-900">Entregado</p>
            </div>
        </div>
    </div>

    <script>
        function deliverModalData() {
            return {
                open: false,
                orderId: null,
                orderNumber: '',
                total: '0.00',
                paid: '0.00',
                balance: '0.00',
                items: [],
                paymentConfirmed: null,
                paymentMethodId: '',
                paymentAmount: '',
                digitalWalletId: '',
                cardId: '',
                paymentGatewayId: '',
                cashRegisterId: '',
                paymentMethods:   @json($paymentMethods),
                digitalWallets:   @json($digitalWallets),
                cards:            @json($cards),
                paymentGateways:  @json($paymentGateways),
                openCashRegisters: @json($openCashRegisters),
                trackingNumber: '',
                processing: false,
                baseUrl: '{{ route('admin.dispatch.index') }}',

                init() {
                    ['modal-evidence-photo', 'modal-evidence-camera'].forEach(id => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.addEventListener('change', () => {
                            const fn = document.getElementById('modal-evidence-filename');
                            if (fn) fn.textContent = el.files[0]?.name || 'Ningún archivo seleccionado';
                        });
                    });
                },

                get selectedMethodKind() {
                    const pm = this.paymentMethods.find(m => m.id == this.paymentMethodId);
                    if (!pm) return 'plain';
                    const d = (pm.description || '').toLowerCase();
                    if (d.includes('tarjeta') || d.includes('card')) return 'card';
                    if (d.includes('billetera') || d.includes('wallet')) return 'wallet';
                    return 'plain';
                },

                get autoCashRegister() {
                    return this.openCashRegisters.length > 0 ? this.openCashRegisters[0] : null;
                },

                openModal(detail) {
                    const p = v => parseFloat(String(v).replace(/,/g, '')) || 0;
                    this.orderId           = detail.orderId;
                    this.orderNumber       = detail.orderNumber;
                    this.total             = p(detail.total).toFixed(2);
                    this.paid              = p(detail.paid).toFixed(2);
                    this.balance           = (p(detail.total) - p(detail.paid)).toFixed(2);
                    this.items             = detail.items;
                    this.paymentConfirmed  = null;
                    this.paymentMethodId   = '';
                    this.paymentAmount     = parseFloat(this.balance).toFixed(2);
                    this.digitalWalletId   = '';
                    this.cardId            = '';
                    this.paymentGatewayId  = '';
                    this.cashRegisterId    = this.autoCashRegister ? this.autoCashRegister.id : '';
                    this.trackingNumber    = '';
                    this.processing        = false;
                    ['modal-evidence-photo', 'modal-evidence-camera'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    const fn = document.getElementById('modal-evidence-filename');
                    if (fn) fn.textContent = 'Ningún archivo seleccionado';
                    this.open = true;
                },

                async submitModal() {
                    if (this.paymentConfirmed === null) {
                        alert('Por favor indica si se registró el pago.');
                        return;
                    }

                    const needsPayment = this.paymentConfirmed === 1 && parseFloat(this.balance) > 0.01;

                    if (needsPayment && !this.paymentMethodId) {
                        alert('Selecciona el método de pago para registrar el cobro.');
                        return;
                    }

                    const amount = parseFloat(this.paymentAmount) || 0;
                    const balance = parseFloat(this.balance) || 0;

                    if (needsPayment && amount < 0.01) {
                        alert('El monto debe ser mayor a S/ 0.00.');
                        return;
                    }

                    if (needsPayment && amount > balance + 0.01) {
                        alert(`El monto (S/ ${amount.toFixed(2)}) supera el saldo pendiente (S/ ${balance.toFixed(2)}).`);
                        return;
                    }

                    if (needsPayment && this.selectedMethodKind === 'wallet' && !this.digitalWalletId) {
                        alert('Selecciona el tipo de billetera virtual.');
                        return;
                    }

                    if (needsPayment && !this.autoCashRegister) {
                        alert('No hay caja abierta en esta sucursal. Abre una caja para registrar el cobro.');
                        return;
                    }

                    this.processing = true;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const formData  = new FormData();
                    formData.append('payment_confirmed', this.paymentConfirmed === 1 ? '1' : '0');
                    if (this.trackingNumber.trim()) {
                        formData.append('tracking_number', this.trackingNumber.trim());
                    }
                    if (needsPayment) {
                        formData.append('payment_method_id', this.paymentMethodId);
                        formData.append('payment_amount', amount.toFixed(2));
                        if (this.cashRegisterId) {
                            formData.append('cash_register_id', this.cashRegisterId);
                        }
                        if (this.selectedMethodKind === 'wallet' && this.digitalWalletId) {
                            formData.append('digital_wallet_id', this.digitalWalletId);
                        }
                        if (this.selectedMethodKind === 'card' && this.cardId) {
                            formData.append('card_id', this.cardId);
                        }
                    }
                    const cameraInput = document.getElementById('modal-evidence-camera');
                    const photoInput  = document.getElementById('modal-evidence-photo');
                    const evidenceFile = cameraInput?.files[0] || photoInput?.files[0];
                    if (evidenceFile) {
                        formData.append('evidence_photo', evidenceFile);
                    }

                    try {
                        const response = await fetch(`${this.baseUrl}/${this.orderId}/entregar`, {
                            method : 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            body   : formData,
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) throw new Error(data.message || 'Error al marcar entregado.');

                        updateDeliveredRow(this.orderId, data.delivered_at);
                        this.open = false;
                        showToast(data.message || 'Pedido marcado como entregado.');
                    } catch (error) {
                        alert(error.message || 'No se pudo actualizar la entrega.');
                    } finally {
                        this.processing = false;
                    }
                },
            };
        }

        function dispatchDeliverModal(orderId, orderNumber, total, items, paid) {
            window.dispatchEvent(new CustomEvent('open-deliver-modal', {
                detail: { orderId, orderNumber, total, items, paid: paid || 0 }
            }));
        }

        // ── Despacho Rápido (venta directa) ─────────────────────────────────────
        // Estructura/maquetado inicial: productos y clientes son datos de ejemplo.
        // Falta conectar con el backend real (catálogo de la sucursal, clientes,
        // y el endpoint que registre la venta).
        function quickDispatchModalData() {
            return {
                open: false,
                processing: false,

                products: @json($products ?? []),
                clients: @json($clients ?? []),
                paymentMethods: @json($paymentMethods ?? []),
                openCashRegisters: @json($openCashRegisters ?? []),
                digitalWallets: @json($digitalWallets ?? []),
                cards: @json($cards ?? []),
                paymentGateways: @json($paymentGateways ?? []),

                productSearch: '',
                productOpen: false,
                cartItems: [],

                clientSearch: '',
                clientOpen: false,
                selectedClientId: null,

                payments: [],

                get totalPaid() {
                    return this.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
                },

                getPaymentMethodKind(methodId) {
                    if (!methodId) return 'plain';
                    const pm = this.paymentMethods.find(m => m.id == methodId);
                    if (!pm) return 'plain';
                    const d = (pm.description || '').toLowerCase();
                    if (d.includes('tarjeta') || d.includes('card')) return 'card';
                    if (d.includes('billetera') || d.includes('wallet')) return 'wallet';
                    return 'plain';
                },

                get autoCashRegister() {
                    return this.openCashRegisters.length > 0 ? this.openCashRegisters[0] : null;
                },

                get filteredProducts() {
                    const term = this.productSearch.toLowerCase().trim();
                    const list = term
                        ? this.products.filter(p => p.name.toLowerCase().includes(term) || (p.code && p.code.toLowerCase().includes(term)))
                        : this.products;
                    return list.slice(0, 10);
                },

                get filteredClients() {
                    const term = this.clientSearch.toLowerCase().trim();
                    const list = term
                        ? this.clients.filter(c =>
                            c.name.toLowerCase().includes(term) || (c.document && c.document.toLowerCase().includes(term)))
                        : this.clients;
                    return list.slice(0, 10);
                },

                get total() {
                    return this.cartItems.reduce((sum, i) => sum + (parseFloat(i.quantity) || 0) * i.unit_price, 0);
                },

                syncSinglePaymentAmount() {
                    if (this.payments.length === 1) {
                        this.payments[0].amount = parseFloat(this.total.toFixed(2));
                    }
                },

                openModal() {
                    this.processing = false;
                    this.productSearch = '';
                    this.productOpen = false;
                    this.clientSearch = '';
                    this.clientOpen = false;
                    this.selectedClientId = null;
                    this.cartItems = [];
                    const defaultPm = this.paymentMethods.length > 0 ? this.paymentMethods[0].id : '';
                    this.payments = [{
                        payment_method_id: defaultPm,
                        amount: 0,
                        digital_wallet_id: '',
                        card_id: '',
                    }];
                    this.open = true;
                },

                addPaymentRow() {
                    const defaultPm = this.paymentMethods.length > 0 ? this.paymentMethods[0].id : '';
                    const diff = Math.max(0, this.total - this.totalPaid);
                    this.payments.push({
                        payment_method_id: defaultPm,
                        amount: parseFloat(diff.toFixed(2)),
                        digital_wallet_id: '',
                        card_id: '',
                    });
                },

                removePaymentRow(index) {
                    if (this.payments.length > 1) {
                        this.payments.splice(index, 1);
                        this.syncSinglePaymentAmount();
                    }
                },

                addProduct(product) {
                    const existing = this.cartItems.find(i => i.product_id === product.id);
                    if (existing) {
                        existing.quantity += 1;
                    } else {
                        this.cartItems.push({
                            product_id: product.id,
                            name: product.name,
                            quantity: 1,
                            unit_price: product.price,
                        });
                    }
                    this.productSearch = '';
                    this.productOpen = false;
                    this.syncSinglePaymentAmount();
                },

                removeProduct(productId) {
                    this.cartItems = this.cartItems.filter(i => i.product_id !== productId);
                    this.syncSinglePaymentAmount();
                },

                updateQty(productId, delta) {
                    const item = this.cartItems.find(i => i.product_id === productId);
                    if (!item) return;
                    const currentQty = parseInt(item.quantity) || 0;
                    item.quantity = Math.max(1, currentQty + delta);
                    this.syncSinglePaymentAmount();
                },

                selectClient(client) {
                    this.selectedClientId = client.id;
                    this.clientSearch = client.name;
                    this.clientOpen = false;
                },

                async submitQuickSale() {
                    if (!this.cartItems.length || this.processing) return;

                    if (!this.payments.length) {
                        alert('Agrega al menos un método de pago.');
                        return;
                    }

                    for (let i = 0; i < this.payments.length; i++) {
                        const pay = this.payments[i];
                        if (!pay.payment_method_id) {
                            alert(`Selecciona el método de pago #${i + 1}.`);
                            return;
                        }
                        if ((parseFloat(pay.amount) || 0) <= 0) {
                            alert(`El monto del pago #${i + 1} debe ser mayor a S/ 0.00.`);
                            return;
                        }
                        const kind = this.getPaymentMethodKind(pay.payment_method_id);
                        if (kind === 'wallet' && !pay.digital_wallet_id) {
                            alert(`Selecciona la billetera virtual para el pago #${i + 1}.`);
                            return;
                        }
                        if (kind === 'card' && !pay.card_id) {
                            alert(`Selecciona el tipo de tarjeta para el pago #${i + 1}.`);
                            return;
                        }
                    }

                    this.processing = true;

                    try {
                        const response = await fetch('{{ route("admin.dispatch.quick-sale") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                person_id: this.selectedClientId,
                                items: this.cartItems.map(i => ({
                                    product_id: i.product_id,
                                    quantity: parseFloat(i.quantity) || 1,
                                    unit_price: i.unit_price,
                                })),
                                payments: this.payments.map(p => ({
                                    payment_method_id: p.payment_method_id,
                                    amount: parseFloat(p.amount) || 0,
                                    cash_register_id: this.autoCashRegister ? this.autoCashRegister.id : null,
                                    digital_wallet_id: p.digital_wallet_id || null,
                                    card_id: p.card_id || null,
                                })),
                            }),
                        });

                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Error al registrar el despacho rápido.');
                        }

                        this.open = false;
                        showToast(data.message || 'Despacho rápido registrado.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 600);
                    } catch (error) {
                        alert(error.message || 'No se pudo registrar la venta.');
                    } finally {
                        this.processing = false;
                    }
                },
            };
        }

        function openQuickDispatchModal() {
            window.dispatchEvent(new CustomEvent('open-quick-dispatch-modal'));
        }

        function updateDeliveredRow(orderId, deliveredAt) {
            const badge = document.getElementById(`delivery-badge-${orderId}`);
            if (badge) {
                badge.className = 'inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700';
                badge.innerHTML = '<i class="ri-checkbox-circle-fill text-xs"></i> Entregado';
                if (deliveredAt) {
                    const dateEl = document.createElement('p');
                    dateEl.className = 'mt-0.5 text-[10px] text-slate-400';
                    dateEl.textContent = deliveredAt;
                    badge.parentElement.appendChild(dateEl);
                }
            }
            const actionCell = document.getElementById(`dispatch-action-${orderId}`);
            if (actionCell) actionCell.innerHTML = '<span class="text-xs text-slate-400">—</span>';

            const row = document.getElementById(`dispatch-row-${orderId}`);
            if (row) {
                row.classList.add('bg-slate-50/40');
                row.classList.remove('hover:bg-slate-50/60');
            }
        }

        function showToast(message) {
            const toast = document.getElementById('dispatch-toast');
            document.getElementById('dispatch-toast-msg').textContent = message;
            toast.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');
            toast.classList.remove('translate-x-[140%]', 'opacity-0', 'pointer-events-none');
            setTimeout(() => {
                toast.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
                toast.classList.add('translate-x-[140%]', 'opacity-0', 'pointer-events-none');
            }, 3000);
        }
    </script>
@endsection
