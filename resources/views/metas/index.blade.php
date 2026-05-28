@extends('layouts.app')

@section('content')
<div x-data="{
    createOpen: false,
    editOpen: false,
    editMeta: {},
    openEdit(meta) {
        this.editMeta = { ...meta };
        this.editOpen = true;
    }
}" class="flex flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestión de Metas</h1>
            <p class="text-sm text-gray-500 mt-1">Registra y realiza seguimiento de las metas de ventas por período.</p>
        </div>
        <button @click="createOpen = true"
            class="inline-flex items-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition-colors">
            <i class="ri-add-line text-base"></i>
            Nueva Meta
        </button>
    </div>

    @php
        $active   = $metas->where('status', 'active');
        $upcoming = $metas->where('status', 'upcoming');
        $finished = $metas->where('status', 'finished');
    @endphp

    {{-- METAS EN CURSO --}}
    @if ($active->isNotEmpty())
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">En curso</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($active as $meta)
                    @include('metas._card', ['meta' => $meta])
                @endforeach
            </div>
        </div>
    @endif

    {{-- METAS PRÓXIMAS --}}
    @if ($upcoming->isNotEmpty())
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Próximas</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($upcoming as $meta)
                    @include('metas._card', ['meta' => $meta])
                @endforeach
            </div>
        </div>
    @endif

    {{-- METAS FINALIZADAS --}}
    @if ($finished->isNotEmpty())
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Finalizadas</h2>
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400 tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">Meta</th>
                            <th class="px-4 py-3 text-left">Período</th>
                            <th class="px-4 py-3 text-right">Meta S/</th>
                            <th class="px-4 py-3 text-right">Alcanzado S/</th>
                            <th class="px-4 py-3 text-right">Meta Unid.</th>
                            <th class="px-4 py-3 text-right">Alcanzado Unid.</th>
                            <th class="px-4 py-3 text-center">Resultado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($finished as $meta)
                            @php
                                $pctA = $meta->target_amount   > 0 ? min(100, ($meta->progress_amount   / $meta->target_amount)   * 100) : null;
                                $pctQ = $meta->target_quantity > 0 ? min(100, ($meta->progress_quantity / $meta->target_quantity) * 100) : null;
                                $pct  = array_filter([$pctA, $pctQ], fn($v) => $v !== null);
                                $avg  = count($pct) ? array_sum($pct) / count($pct) : null;
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $meta->name }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $meta->start_date->format('d/m/Y') }} – {{ $meta->end_date->format('d/m/Y') }}
                                    <span class="ml-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $meta->period_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    {{ $meta->target_amount !== null ? 'S/ '.number_format($meta->target_amount, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-800 font-medium">
                                    {{ $meta->progress_amount !== null ? 'S/ '.number_format($meta->progress_amount, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    {{ $meta->target_quantity !== null ? number_format($meta->target_quantity, 0) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-800 font-medium">
                                    {{ $meta->progress_quantity !== null ? number_format($meta->progress_quantity, 0) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($avg !== null)
                                        <span @class([
                                            'inline-block rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-green-100 text-green-700' => $avg >= 100,
                                            'bg-yellow-100 text-yellow-700' => $avg >= 70 && $avg < 100,
                                            'bg-red-100 text-red-700' => $avg < 70,
                                        ])>{{ number_format($avg, 1) }}%</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="openEdit({{ json_encode([
                                            'id'              => $meta->id,
                                            'name'            => $meta->name,
                                            'period_type'     => $meta->period_type,
                                            'start_date'      => $meta->start_date->toDateString(),
                                            'end_date'        => $meta->end_date->toDateString(),
                                            'target_amount'   => $meta->target_amount,
                                            'target_quantity' => $meta->target_quantity,
                                            'notes'           => $meta->notes,
                                        ]) }})"
                                            class="rounded-lg bg-amber-100 p-2 text-amber-600 hover:bg-amber-200 transition-colors">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.metas.destroy', $meta) }}"
                                            onsubmit="return confirm('¿Eliminar esta meta?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-100 p-2 text-red-600 hover:bg-red-200 transition-colors">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($metas->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white flex flex-col items-center justify-center py-24 gap-4 text-gray-400">
            <i class="ri-bar-chart-line text-5xl"></i>
            <p class="text-lg font-medium">Sin metas registradas</p>
            <p class="text-sm">Haz clic en <strong>Nueva Meta</strong> para comenzar.</p>
        </div>
    @endif

    {{-- ============================================================
         MODAL: CREAR META
    ============================================================ --}}
    <template x-teleport="body">
        <div x-show="createOpen"
            class="fixed inset-0 z-[99999] flex items-center justify-center backdrop-blur-sm bg-black/30 px-4"
            style="display:none"
            @click.self="createOpen = false">
            <div x-show="createOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-gray-800">Nueva Meta</h2>
                    <button @click="createOpen = false" class="rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.metas.store') }}" class="flex flex-col gap-4 px-6 py-5">
                    @csrf
                    @include('metas._form', ['meta' => null])
                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" @click="createOpen = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-2 text-sm font-semibold text-white transition-colors">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         MODAL: EDITAR META
    ============================================================ --}}
    <template x-teleport="body">
        <div x-show="editOpen"
            class="fixed inset-0 z-[99999] flex items-center justify-center backdrop-blur-sm bg-black/30 px-4"
            style="display:none"
            @click.self="editOpen = false">
            <div x-show="editOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-gray-800">Editar Meta</h2>
                    <button @click="editOpen = false" class="rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <form method="POST" :action="`/admin/metas/${editMeta.id}`" class="flex flex-col gap-4 px-6 py-5">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-gray-700">Nombre de la meta <span class="text-red-500">*</span></label>
                        <input type="text" name="name" :value="editMeta.name" required maxlength="255"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-gray-700">Tipo de período <span class="text-red-500">*</span></label>
                        <select name="period_type" x-model="editMeta.period_type"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <option value="week">Semanal</option>
                            <option value="biweek">Quincenal</option>
                            <option value="month">Mensual</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Fecha inicio <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" :value="editMeta.start_date" required
                                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Fecha fin <span class="text-red-500">*</span></label>
                            <input type="date" name="end_date" :value="editMeta.end_date" required
                                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Meta en monto (S/)</label>
                            <input type="number" name="target_amount" :value="editMeta.target_amount" min="100" step="0.10"
                                placeholder="Ej: 50000"
                                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-gray-700">Meta en unidades</label>
                            <input type="number" name="target_quantity" :value="editMeta.target_quantity" min="0" step="1"
                                placeholder="Ej: 10000"
                                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-gray-700">Notas (opcional)</label>
                        <textarea name="notes" rows="2" maxlength="1000" x-text="editMeta.notes"
                            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" @click="editOpen = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-2 text-sm font-semibold text-white transition-colors">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection
