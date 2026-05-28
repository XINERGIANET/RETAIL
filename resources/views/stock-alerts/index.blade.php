@extends('layouts.app')

@section('content')
<div class="p-4 mx-auto max-w-5xl sm:p-6">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Alertas de Stock Bajo</h1>
        <p class="text-sm text-slate-500 mt-1">Productos cuyo stock actual está al nivel o por debajo del mínimo configurado.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Producto</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Stock actual</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Stock mínimo</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Diferencia</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Desde</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                    <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $alert->product?->description ?? 'Producto #'.$alert->product_id }}
                            @if ($alert->product?->code)
                                <span class="ml-1 text-xs text-slate-400">({{ $alert->product->code }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold
                                {{ $alert->current_stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                {{ $alert->current_stock }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $alert->stock_minimum }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-red-600 font-semibold">{{ $alert->current_stock - $alert->stock_minimum }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            {{ $alert->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-2 h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            No hay alertas de stock activas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alerts->hasPages())
        <div class="flex items-center justify-between border-t border-slate-50 px-4 py-3">
            <p class="text-xs font-bold text-slate-400">
                Mostrando <span class="text-slate-700">{{ $alerts->firstItem() }}</span>–<span class="text-slate-700">{{ $alerts->lastItem() }}</span>
                de <span class="text-slate-700">{{ $alerts->total() }}</span>
            </p>
            <div class="pagination-simple">
                {{ $alerts->links('vendor.pagination.forced') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
