@php
    $isDispatcher = str_contains(
        strtolower(auth()->user()?->profile?->name ?? ''),
        'despach'
    );
@endphp

@if (!$isDispatcher)

@php
    $branchId = (int) session()->get('branch_id');
    if ($branchId <= 0) {
        $branchId = (int) (optional(auth()->user())->person->branch_id ?? 0);
    }

    $cashRegisters = \App\Models\CashRegister::query()
        ->where('branch_id', $branchId)
        ->where('status', '1')
        ->get()
        ->map(function ($box) use ($branchId) {
            $box->is_open = \App\Models\CashShiftRelation::query()
                ->where('branch_id', $branchId)
                ->where('status', '1')
                ->whereHas('cashMovementStart', function ($q) use ($box) {
                    $q->where('cash_register_id', $box->id);
                })->exists();
            return $box;
        });

    $showBoxModal = !session()->has('box_prompt_shown');
    if ($showBoxModal) {
        session()->put('box_prompt_shown', true);
    }
@endphp

<template x-teleport="body">
    <div x-data="{ showBoxModal: {{ $showBoxModal ? 'true' : 'false' }} }"
         @open-box-modal.window="showBoxModal = true"
         x-show="showBoxModal"
         style="display: none; z-index: 9999999;"
         class="fixed inset-0 flex items-center justify-center p-4 sm:p-0 print:hidden">
        
        <div x-show="showBoxModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-md"
             @click="showBoxModal = false"></div>

        <div x-show="showBoxModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10 transform transition-all">
            
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900">Seleccionar Caja</h3>
                <button type="button" @click="showBoxModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <p class="text-sm font-medium text-slate-500 mb-6">
                    Selecciona la caja de trabajo para esta sesión. Todo el sistema operará con esa caja hasta que la cambies.
                </p>

                <div class="space-y-3">
                    @forelse($cashRegisters as $box)
                    <a href="{{ route('admin.petty-cash.index', ['cash_register_id' => $box->id]) }}" 
                       class="block border rounded-xl p-4 transition-all {{ $box->is_open ? 'border-orange-200 bg-orange-50 hover:bg-orange-100' : 'border-slate-100 bg-slate-50 hover:bg-slate-100' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $box->is_open ? 'bg-orange-100 text-orange-600' : 'bg-white text-slate-400 shadow-sm' }}">
                                    <i class="ri-store-2-line text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black {{ $box->is_open ? 'text-slate-900' : 'text-slate-700' }}">{{ $box->number }}</h4>
                                    @if($box->is_open)
                                    <p class="text-[10px] font-bold text-orange-600 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        Activa actualmente
                                    </p>
                                    @else
                                    <p class="text-[10px] font-medium text-slate-400">Click para abrir</p>
                                    @endif
                                </div>
                            </div>
                            @if($box->is_open)
                            <div class="text-orange-500">
                                <i class="ri-checkbox-circle-fill text-xl"></i>
                            </div>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div class="py-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
                        <i class="ri-store-3-line text-3xl text-slate-300 mb-2"></i>
                        <p class="text-sm font-bold text-slate-500">No hay cajas disponibles</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</template>

@endif
