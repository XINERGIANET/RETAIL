@php
    $isActive   = $meta->status === 'active';
    $isUpcoming = $meta->status === 'upcoming';

    // Porcentaje monto
    $pctA = ($meta->target_amount !== null && (float)$meta->target_amount > 0)
        ? min(100, ((float)$meta->progress_amount / (float)$meta->target_amount) * 100)
        : null;

    // Porcentaje cantidad
    $pctQ = ($meta->target_quantity !== null && (float)$meta->target_quantity > 0)
        ? min(100, ((float)$meta->progress_quantity / (float)$meta->target_quantity) * 100)
        : null;

    // Color barra según progreso
    $colorClass = fn($pct) => match(true) {
        $pct === null      => '',
        $pct >= 100        => 'bg-green-500',
        $pct >= 70         => 'bg-orange-400',
        default            => 'bg-red-400',
    };

    $daysTotal     = $meta->start_date->diffInDays($meta->end_date) + 1;
    $daysPassed    = $isActive ? (now()->diffInDays($meta->start_date) + 1) : 0;
    $daysRemaining = $isActive ? max(0, $meta->end_date->diffInDays(now())) : 0;
@endphp

<div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden flex flex-col">

    {{-- Color strip top --}}
    <div @class([
        'h-1.5 w-full',
        'bg-orange-400' => $isActive,
        'bg-blue-300'   => $isUpcoming,
    ])></div>

    <div class="flex flex-col gap-4 p-5">

        {{-- Title row --}}
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="font-semibold text-gray-800 leading-snug">{{ $meta->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $meta->start_date->format('d/m/Y') }} – {{ $meta->end_date->format('d/m/Y') }}
                </p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-orange-100 text-orange-600' => $isActive,
                    'bg-blue-100 text-blue-600'     => $isUpcoming,
                ])>
                    {{ $isActive ? 'En curso' : 'Próxima' }}
                </span>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $meta->period_label }}</span>
            </div>
        </div>

        {{-- Progress: Monto --}}
        @if ($meta->target_amount !== null)
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-600 flex items-center gap-1"><i class="ri-money-dollar-circle-line"></i> Monto S/</span>
                    <span class="font-bold text-gray-800">
                        @if ($isActive)
                            S/ {{ number_format((float)$meta->progress_amount, 2) }}
                            <span class="text-gray-400 font-normal">/ S/ {{ number_format((float)$meta->target_amount, 2) }}</span>
                        @else
                            Meta: S/ {{ number_format((float)$meta->target_amount, 2) }}
                        @endif
                    </span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $colorClass($pctA) }}"
                        style="width: {{ $isActive ? number_format($pctA ?? 0, 1) : 0 }}%"></div>
                </div>
                @if ($isActive && $pctA !== null)
                    <p class="text-right text-xs font-semibold {{ $pctA >= 100 ? 'text-green-600' : ($pctA >= 70 ? 'text-orange-500' : 'text-red-500') }}">
                        {{ number_format($pctA, 1) }}%
                    </p>
                @endif
            </div>
        @endif

        {{-- Progress: Cantidad --}}
        @if ($meta->target_quantity !== null)
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-600 flex items-center gap-1"><i class="ri-stack-line"></i> Unidades</span>
                    <span class="font-bold text-gray-800">
                        @if ($isActive)
                            {{ number_format((float)$meta->progress_quantity, 0) }}
                            <span class="text-gray-400 font-normal">/ {{ number_format((float)$meta->target_quantity, 0) }}</span>
                        @else
                            Meta: {{ number_format((float)$meta->target_quantity, 0) }}
                        @endif
                    </span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $colorClass($pctQ) }}"
                        style="width: {{ $isActive ? number_format($pctQ ?? 0, 1) : 0 }}%"></div>
                </div>
                @if ($isActive && $pctQ !== null)
                    <p class="text-right text-xs font-semibold {{ $pctQ >= 100 ? 'text-green-600' : ($pctQ >= 70 ? 'text-orange-500' : 'text-red-500') }}">
                        {{ number_format($pctQ, 1) }}%
                    </p>
                @endif
            </div>
        @endif

        {{-- Footer: días --}}
        @if ($isActive)
            <p class="text-xs text-gray-400">
                <i class="ri-calendar-line"></i>
                {{ $daysRemaining }} día(s) restante(s) de {{ $daysTotal }}
            </p>
        @endif

    </div>

    {{-- Actions --}}
    <div class="border-t border-gray-50 px-5 py-3 flex justify-end gap-2 bg-gray-50/50">
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
            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-200 transition-colors">
            <i class="ri-pencil-line"></i> Editar
        </button>
        <form method="POST" action="{{ route('admin.metas.destroy', $meta) }}"
            onsubmit="return confirm('¿Eliminar esta meta?')">
            @csrf @method('DELETE')
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-200 transition-colors">
                <i class="ri-delete-bin-line"></i> Eliminar
            </button>
        </form>
    </div>

</div>
