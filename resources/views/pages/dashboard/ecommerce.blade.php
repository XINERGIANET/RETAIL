@extends('layouts.app')

@section('meta')
    <meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <style>
        .hover-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hover-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.1);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>

    @php
        $d = $dashboardData ?? [];
        $incomeByDay = collect($d['incomeByDay'] ?? []);
        $maxIncome = (float) max(1, (float) $incomeByDay->max('amount'));
        $birthdays = collect($d['birthdays'] ?? []);
        $salesDetails = collect($d['salesTodayDetails'] ?? []);
        
        $rangeStart = \Carbon\Carbon::parse($d['dateFrom'] ?? now()->toDateString());
        $rangeEnd = \Carbon\Carbon::parse($d['dateTo'] ?? now()->toDateString());
        $periodLabel = $rangeStart->isSameDay($rangeEnd) 
            ? $rangeStart->format('d/m/Y') 
            : ($rangeStart->format('d/m/Y') . ' - ' . $rangeEnd->format('d/m/Y'));

        // Chart calculations
        $count = count($incomeByDay);
        $yMax = ceil($maxIncome / 100) * 100;
        if ($yMax == 0) $yMax = 1000;
        
        $chartPoints = [];
        foreach($incomeByDay as $index => $row) {
            $x = ($count > 1) ? ($index / ($count - 1)) * 1000 : 500;
            $y = 100 - (($row['amount'] / $yMax) * 100);
            $chartPoints[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => (string) ($row['label'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
            ];
        }

        $path = "";
        if (count($chartPoints) > 0) {
            $path = "M " . $chartPoints[0]['x'] . " " . $chartPoints[0]['y'];
            for ($i = 0; $i < count($chartPoints) - 1; $i++) {
                $curr = $chartPoints[$i];
                $next = $chartPoints[$i+1];
                $cp1x = $curr['x'] + ($next['x'] - $curr['x']) / 2;
                $path .= " C $cp1x " . $curr['y'] . ", $cp1x " . $next['y'] . ", " . $next['x'] . " " . $next['y'];
            }
        }
        $areaPath = $path ? ($path . " L 1000 100 L 0 100 Z") : "";
    @endphp

    <div class="mb-8 flex items-center justify-between print:hidden">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Dashboard Retail</h1>
            <p class="text-sm font-medium text-slate-500">Resumen operativo de ventas y movimientos</p>
        </div>
        
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-3">
            @if(request('view_id')) <input type="hidden" name="view_id" value="{{ request('view_id') }}"> @endif
            <div class="flex items-center bg-white rounded-2xl border border-slate-200 p-1.5 shadow-sm">
                <input type="date" name="date_from" value="{{ $d['dateFrom'] }}" class="border-0 bg-transparent text-xs font-bold text-slate-700 focus:ring-0">
                <span class="text-slate-300 mx-1">/</span>
                <input type="date" name="date_to" value="{{ $d['dateTo'] }}" class="border-0 bg-transparent text-xs font-bold text-slate-700 focus:ring-0">
            </div>
            <button type="submit" class="h-11 px-5 bg-slate-900 text-white rounded-2xl text-xs font-black hover:bg-slate-800 transition-all shadow-lg shadow-slate-200 flex items-center gap-2">
                <i class="ri-refresh-line"></i> Actualizar
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Venta Total -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 hover-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i class="ri-shopping-bag-3-fill text-2xl"></i>
                </div>
                <span class="text-[10px] font-black text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Ventas</span>
            </div>
            <p class="text-sm font-bold text-slate-400 mb-1">Total Facturado</p>
            <h3 class="text-2xl font-black text-slate-900">S/ {{ number_format($d['todayInvoiced'] ?? 0, 2) }}</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-tighter">{{ $periodLabel }}</p>
        </div>

        <!-- Ingresos Caja -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 hover-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="ri-money-dollar-circle-fill text-2xl"></i>
                </div>
                <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Ingresos</span>
            </div>
            <p class="text-sm font-bold text-slate-400 mb-1">Ingresos de Caja</p>
            <h3 class="text-2xl font-black text-slate-900">S/ {{ number_format($d['income'] ?? 0, 2) }}</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-tighter">{{ $periodLabel }}</p>
        </div>

        <!-- Egresos Caja -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 hover-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i class="ri-arrow-left-right-line text-2xl"></i>
                </div>
                <span class="text-[10px] font-black text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Egresos</span>
            </div>
            <p class="text-sm font-bold text-slate-400 mb-1">Egresos de Caja</p>
            <h3 class="text-2xl font-black text-slate-900">S/ {{ number_format($d['expenses'] ?? 0, 2) }}</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-tighter">{{ $periodLabel }}</p>
        </div>

        <!-- Utilidad -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 hover-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="ri-safe-2-fill text-2xl"></i>
                </div>
                <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Balance</span>
            </div>
            <p class="text-sm font-bold text-slate-400 mb-1">Utilidad Estimada</p>
            <h3 class="text-2xl font-black text-slate-900">S/ {{ number_format($d['utility'] ?? 0, 2) }}</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-tighter">{{ $periodLabel }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Gráfico de Tendencia -->
        <div class="lg:col-span-8 bg-white p-8 rounded-[2.5rem] border border-slate-100 relative overflow-hidden" 
             x-data="{ 
                points: @js($chartPoints),
                activeIndex: {{ count($chartPoints) - 1 }},
                get active() { return this.points[this.activeIndex] || null }
             }">
            <div class="flex items-center justify-between mb-10 relative z-10">
                <div>
                    <h3 class="text-xl font-black text-slate-900">Tendencia de Ventas</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ingresos por día</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-brand-500 uppercase mb-1">Seleccionado</p>
                    <h4 class="text-xl font-black text-slate-900" x-text="active ? 'S/ ' + active.amount.toLocaleString('es-PE', {minimumFractionDigits: 2}) : '-'"></h4>
                </div>
            </div>

            <div class="relative h-[300px] w-full">
                @if($path)
                <svg viewBox="0 0 1000 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                    <defs>
                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#FF9F36" stop-opacity="0.15" />
                            <stop offset="100%" stop-color="#FF9F36" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path d="{{ $areaPath }}" fill="url(#chartGrad)" />
                    <path d="{{ $path }}" fill="none" stroke="#EE6D00" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    
                    @foreach($chartPoints as $i => $point)
                        <circle
                            cx="{{ $point['x'] }}"
                            cy="{{ $point['y'] }}"
                            r="5"
                            fill="white"
                            stroke="#EE6D00"
                            stroke-width="2"
                            @mouseenter="activeIndex = {{ $i }}"
                            class="cursor-pointer" />
                    @endforeach
                </svg>
                @else
                <div class="h-full flex items-center justify-center text-slate-300 italic text-sm">Sin datos para graficar</div>
                @endif
            </div>

            <div class="flex justify-between mt-6 pt-6 border-t border-slate-50 overflow-x-auto gap-4 custom-scrollbar">
                @foreach($incomeByDay as $i => $row)
                <button type="button" @mouseenter="activeIndex = {{ $i }}" 
                        class="px-3 py-2 rounded-xl text-[10px] font-black transition-all whitespace-nowrap"
                        :class="activeIndex == {{ $i }} ? 'bg-brand-600 text-white shadow-lg' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'">
                    {{ $row['label'] }}
                </button>
                @endforeach
            </div>
        </div>

        <!-- Próximos Cumpleaños -->
        <div class="lg:col-span-4 bg-white p-8 rounded-[2.5rem] border border-slate-100 flex flex-col">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <i class="ri-cake-3-fill text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-900">Cumpleaños</h3>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Esta semana</p>
                </div>
            </div>

            <div class="space-y-4 flex-1 overflow-y-auto custom-scrollbar pr-2">
                @forelse($birthdays as $bday)
                <div class="group flex items-center gap-4 p-4 rounded-[1.5rem] bg-slate-50 hover:bg-orange-50 border border-transparent hover:border-orange-100 transition-all cursor-pointer">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-black text-xs group-hover:bg-orange-600 group-hover:text-white transition-all">
                        {{ substr($bday->first_name, 0, 1) }}{{ substr($bday->last_name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-black text-slate-800 uppercase">{{ $bday->first_name }} {{ $bday->last_name }}</p>
                        <p class="text-[10px] font-bold text-slate-400">{{ \Carbon\Carbon::parse($bday->fecha_nacimiento)->format('d \d\e M') }}</p>
                    </div>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $bday->phone) }}" target="_blank" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all">
                        <i class="ri-whatsapp-line"></i>
                    </a>
                </div>
                @empty
                <div class="py-12 text-center opacity-30">
                    <i class="ri-ghost-smile-line text-4xl mb-2"></i>
                    <p class="text-xs italic">Nadie cumple años esta semana</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Detalle de Ventas -->
    <div class="bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden mb-8">
        <div class="p-8 border-b border-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i class="ri-file-list-3-fill text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-900">Movimientos Recientes</h3>
                    <p class="text-[10px] font-black text-brand-500 uppercase tracking-widest">Periodo seleccionado</p>
                </div>
            </div>
            <a href="{{ route('admin.sales.index') }}" class="px-6 py-2 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase hover:bg-slate-200 transition-all">Ver todas</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Documento</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-wider text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($salesDetails as $sale)
                    <tr class="hover:bg-slate-50/50 transition-all cursor-pointer">
                        <td class="px-8 py-5 text-xs font-bold text-slate-500">{{ \Carbon\Carbon::parse($sale->movement->moved_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-8 py-5">
                            <span class="text-xs font-black text-slate-900">{{ $sale->movement->number }}</span>
                            <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-black bg-slate-100 text-slate-500 uppercase">{{ $sale->movement->documentType->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-8 py-5 text-xs font-black text-slate-700 uppercase">{{ $sale->movement->person_name }}</td>
                        <td class="px-8 py-5 text-right font-black text-slate-900">S/ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-300 italic text-sm font-medium">No se registraron ventas en este periodo</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
