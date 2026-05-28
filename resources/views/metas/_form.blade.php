{{-- Partial: campos del formulario de meta (crear / editar) --}}
@php $old = old(); @endphp

<div class="flex flex-col gap-1">
    <label class="text-sm font-medium text-gray-700">Nombre de la meta <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $meta?->name) }}" required maxlength="255"
        placeholder="Ej: Meta de ventas – Semana 22"
        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
</div>

<div x-data="{
    periodType: '{{ old('period_type', $meta?->period_type ?? 'month') }}',
    startDate: '{{ old('start_date', $meta?->start_date?->toDateString() ?? now()->toDateString()) }}',
    get endDate() {
        if (!this.startDate) return '';
        const d = new Date(this.startDate);
        if (this.periodType === 'week')   { d.setDate(d.getDate() + 6);  return d.toISOString().slice(0,10); }
        if (this.periodType === 'biweek') { d.setDate(d.getDate() + 13); return d.toISOString().slice(0,10); }
        if (this.periodType === 'month')  { d.setMonth(d.getMonth() + 1); d.setDate(d.getDate() - 1); return d.toISOString().slice(0,10); }
        return this.customEnd;
    },
    customEnd: '{{ old('end_date', $meta?->end_date?->toDateString() ?? '') }}',
}" class="flex flex-col gap-4">

    <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-gray-700">Tipo de período <span class="text-red-500">*</span></label>
        <select name="period_type" x-model="periodType"
            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
            <option value="week">Semanal (7 días)</option>
            <option value="biweek">Quincenal (15 días)</option>
            <option value="month">Mensual (1 mes)</option>
            <option value="custom">Personalizado</option>
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">Fecha inicio <span class="text-red-500">*</span></label>
            <input type="date" name="start_date" x-model="startDate" required
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">Fecha fin <span class="text-red-500">*</span></label>
            <template x-if="periodType !== 'custom'">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="end_date" :value="endDate" />
                    <div class="w-full rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-500 select-none" x-text="endDate || '—'"></div>
                </div>
            </template>
            <template x-if="periodType === 'custom'">
                <input type="date" name="end_date" x-model="customEnd" required
                    class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
            </template>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-gray-700">Meta en monto (S/)</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">S/</span>
            <input type="number" name="target_amount" value="{{ old('target_amount', $meta?->target_amount) }}"
                min="100" step="0.10" placeholder="50,000.00"
                class="w-full rounded-xl border border-gray-200 pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
        </div>
        <p class="text-xs text-gray-400">Deja vacío si no aplica</p>
    </div>
    <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-gray-700">Meta en unidades</label>
        <input type="number" name="target_quantity" value="{{ old('target_quantity', $meta?->target_quantity) }}"
            min="0" step="1" placeholder="10,000"
            class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
        <p class="text-xs text-gray-400">Deja vacío si no aplica</p>
    </div>
</div>

<div class="flex flex-col gap-1">
    <label class="text-sm font-medium text-gray-700">Notas (opcional)</label>
    <textarea name="notes" rows="2" maxlength="1000"
        class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none">{{ old('notes', $meta?->notes) }}</textarea>
</div>
