@extends('layouts.app')

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="Pedidos de Venta" />

        <x-common.component-card title="Pedidos de Venta" desc="Gestión de pedidos a crédito. Registra, sigue y factura pedidos de clientes.">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('admin.sale-orders.index') }}"
                class="mb-5 flex flex-col gap-3">

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <div class="w-36 flex-none">
                        <x-form.select-autocomplete name="per_page" :value="$perPage"
                            :options="collect([10, 20, 50, 100])->map(fn($n) => ['value' => $n, 'label' => $n . ' / página'])->values()->all()"
                            :submit-on-change="true"
                            inputClass="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800" />
                    </div>

                    <div class="relative flex-1 min-w-[200px]">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por número o cliente"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400" />
                    </div>

                    <div class="w-48 flex-none">
                        <x-form.select-autocomplete name="status" :value="$status" :options="[
                            ['value' => 'all',       'label' => 'Todos los estados'],
                            ['value' => 'draft',     'label' => 'Pago Pendiente'],
                            ['value' => 'partial',   'label' => 'Pago parcial'],
                            ['value' => 'completed', 'label' => 'Completado'],
                            ['value' => 'cancelled', 'label' => 'Cancelado'],
                        ]" :submit-on-change="true"
                            inputClass="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit"
                            class="inline-flex h-11 items-center gap-2 rounded-lg px-5 text-sm font-semibold text-white color_button shadow-theme-xs">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </button>
                        <a href="{{ route('admin.sale-orders.index') }}"
                            class="inline-flex h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <i class="ri-refresh-line"></i>
                            <span>Limpiar</span>
                        </a>
                        <a href="{{ route('admin.sale-orders.create') }}"
                            class="inline-flex h-11 items-center gap-2 rounded-lg px-5 text-sm font-semibold text-white shadow-theme-xs"
                            style="background:linear-gradient(90deg,#ff7a00,#ff4d00); box-shadow:0 10px 20px rgba(249,115,22,0.2);">
                            <i class="ri-add-line"></i>
                            <span>Nuevo pedido</span>
                        </a>
                    </div>
                </div>
            </form>

            {{-- Tabla --}}
            <div class="table-responsive rounded-xl border border-gray-200 bg-white" x-data="{ openRow: null }">
                <table class="w-full" style="min-width: 640px;">
                    <thead>
                        <tr style="background:linear-gradient(90deg,#ff7a00,#ff4d00);">
                            @foreach(['', 'Número', 'Cliente', 'Total', 'Estado', 'Acciones'] as $heading)
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-white whitespace-nowrap">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            @php
                                $balance = (float) $order->total - (float) $order->paid;
                                $statusMap = [
                                    'draft'     => ['label' => 'Pago Pendiente', 'class' => 'bg-slate-100 text-slate-700'],
                                    'partial'   => ['label' => 'Pago parcial', 'class' => 'bg-amber-100 text-amber-700'],
                                    'completed' => ['label' => 'Completado', 'class' => 'bg-emerald-100 text-emerald-700'],
                                    'cancelled' => ['label' => 'Cancelado',  'class' => 'bg-red-100 text-red-700'],
                                ];
                                $st = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-gray-100 text-gray-700'];
                            @endphp

                            {{-- Fila principal --}}
                            <tr class="border-b border-gray-100 transition hover:bg-orange-50/40">

                                {{-- Toggle --}}
                                <td class="px-4 py-3 text-center">
                                    <button type="button"
                                        @click="openRow === {{ $order->id }} ? openRow = null : openRow = {{ $order->id }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:opacity-80"
                                        style="background:linear-gradient(135deg,#ff7a00,#ff4d00);">
                                        <i class="ri-add-line text-sm" x-show="openRow !== {{ $order->id }}"></i>
                                        <i class="ri-subtract-line text-sm" x-show="openRow === {{ $order->id }}" x-cloak></i>
                                    </button>
                                </td>

                                {{-- Número --}}
                                <td class="px-4 py-3 text-center">
                                    <p class="text-sm font-bold text-gray-900">{{ $order->number }}</p>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Pedido de venta</p>
                                </td>

                                {{-- Cliente --}}
                                <td class="px-4 py-3 text-sm text-gray-700 max-w-[200px] truncate">
                                    {{ $order->person_name ?? 'Público general' }}
                                </td>

                                {{-- Total --}}
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-sm font-bold" style="color:#ff6a00;">
                                        S/ {{ number_format((float) $order->total, 2) }}
                                    </span>
                                </td>

                                {{-- Estado --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $st['class'] }}">
                                        {{ $st['label'] }}
                                    </span>
                                    @if ($order->delivery?->status === 'ENTREGADO')
                                        <br>
                                        <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700">
                                            <i class="ri-checkbox-circle-line mr-1"></i> Entregado
                                        </span>
                                    @elseif ($order->delivery?->status === 'EN_PROCESO')
                                        <br>
                                        <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700">
                                            <i class="ri-truck-line mr-1"></i> Por enviar
                                        </span>
                                    @elseif ($order->delivery?->status === 'PENDIENTE')
                                        <br>
                                        <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700">
                                            <i class="ri-time-line mr-1"></i> Por entregar
                                        </span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('admin.sale-orders.show', $order) }}"
                                            title="Ver detalle"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-80"
                                            style="background:#FBBF24;">
                                            <i class="ri-eye-line text-sm"></i>
                                        </a>
                                        <a href="{{ route('admin.sale-orders.ticket', $order) }}" target="_blank"
                                            title="Imprimir ticket"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-80"
                                            style="background:#8B5CF6;">
                                            <i class="ri-printer-line text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            {{-- Fila expandida --}}
                            <tr x-show="openRow === {{ $order->id }}" x-cloak
                                class="bg-gray-50/70 border-b border-gray-100">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">ID</p>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-800">#{{ $order->id }}</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pagado</p>
                                            <p class="mt-0.5 text-sm font-semibold text-emerald-600">
                                                S/ {{ number_format((float) $order->paid, 2) }}
                                            </p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saldo</p>
                                            <p class="mt-0.5 text-sm font-semibold {{ $balance > 0.001 ? 'text-amber-600' : 'text-emerald-600' }}">
                                                S/ {{ number_format($balance, 2) }}
                                            </p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Vence</p>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-800">
                                                {{ $order->due_date?->format('d/m/Y') ?? '-' }}
                                            </p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Creado</p>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-800">
                                                {{ $order->created_at->format('d/m/Y') }}
                                            </p>
                                        </div>

                                        @if ($order->created_by_name)
                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Atendido por</p>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-800">{{ $order->created_by_name }}</p>
                                        </div>
                                        @endif

                                        {{-- Entrega --}}
                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm" id="delivery-card-{{ $order->id }}">
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Entrega</p>
                                            @if ($order->delivery?->status === 'ENTREGADO')
                                                <p class="mt-0.5 text-sm font-semibold text-emerald-600">
                                                    <i class="ri-checkbox-circle-line"></i> Entregado
                                                </p>
                                                @if ($order->status !== 'completed')
                                                    <button type="button" onclick="saleOrderDelivery({{ $order->id }}, 'PENDIENTE')"
                                                        class="mt-1 text-xs text-amber-600 underline hover:no-underline">
                                                        Revertir a pendiente
                                                    </button>
                                                @endif
                                            @elseif ($order->delivery?->status === 'EN_PROCESO')
                                                <p class="mt-0.5 text-sm font-semibold text-blue-600">
                                                    <i class="ri-truck-line"></i> Por enviar
                                                </p>
                                                <button type="button" onclick="saleOrderDelivery({{ $order->id }}, 'ENTREGADO')"
                                                    class="mt-1 text-xs text-emerald-600 underline hover:no-underline">
                                                    Marcar como entregado
                                                </button>
                                            @elseif ($order->delivery?->status === 'PENDIENTE')
                                                <p class="mt-0.5 text-sm font-semibold text-red-600">
                                                    <i class="ri-time-line"></i> Por entregar
                                                </p>
                                                <button type="button" onclick="saleOrderDelivery({{ $order->id }}, 'ENTREGADO')"
                                                    class="mt-1 text-xs text-emerald-600 underline hover:no-underline">
                                                    Marcar como entregado
                                                </button>
                                            @else
                                                <p class="mt-0.5 text-sm text-gray-400">Sin registro</p>
                                                <button type="button" onclick="saleOrderDelivery({{ $order->id }}, 'PENDIENTE')"
                                                    class="mt-1 text-xs text-blue-600 underline hover:no-underline">
                                                    Registrar
                                                </button>
                                            @endif
                                        </div>

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16">
                                    <div class="flex flex-col items-center gap-3 text-center">
                                        <div class="rounded-full bg-gray-100 p-4 text-gray-400">
                                            <i class="ri-file-list-3-line text-2xl"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700">No hay pedidos registrados.</p>
                                        <p class="text-sm text-gray-400">Crea el primer pedido para comenzar.</p>
                                        <a href="{{ route('admin.sale-orders.create') }}"
                                            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white"
                                            style="background:linear-gradient(90deg,#ff7a00,#ff4d00); box-shadow:0 10px 20px rgba(249,115,22,0.2);">
                                            <i class="ri-add-line"></i>
                                            <span>Crear primer pedido</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer: paginación + total filtrado --}}
            <div class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-sm text-gray-500">
                    Mostrando
                    <span class="font-semibold text-gray-700">{{ $orders->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700">{{ $orders->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700">{{ $orders->total() }}</span>
                    &nbsp;|&nbsp;
                    Total filtrado:
                    <span class="font-bold" style="color:#ff6a00;">S/ {{ number_format($filteredTotal, 2) }}</span>
                </p>

                @if ($orders->hasPages())
                    {{ $orders->links() }}
                @endif
            </div>

        </x-common.component-card>
    </div>
@endsection

@push('scripts')
<script>
async function saleOrderDelivery(orderId, status) {
    const card = document.getElementById('delivery-card-' + orderId);
    if (card) card.style.opacity = '0.5';

    try {
        const res = await fetch(`/admin/pedidos-venta/${orderId}/entrega`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ delivery_status: status }),
        });

        if (!res.ok) throw new Error(await res.text());
        window.location.reload();
    } catch (e) {
        alert('Error al actualizar entrega: ' + e.message);
        if (card) card.style.opacity = '1';
    }
}
</script>
@endpush
