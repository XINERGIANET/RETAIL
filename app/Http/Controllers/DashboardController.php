<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashMovements;
use App\Models\Person;
use App\Models\SalesMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchId = (int) $request->session()->get('branch_id');
        if ($branchId <= 0) {
            $branchId = (int) (optional($request->user())->person->branch_id ?? 0);
        }
        $companyId = (int) Branch::query()->where('id', $branchId)->value('company_id');
        $dateFrom = Carbon::parse((string) $request->input('date_from', now()->startOfWeek()->toDateString()))->startOfDay();
        $dateTo = Carbon::parse((string) $request->input('date_to', now()->toDateString()))->endOfDay();
        
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }
        
        $todayInvoiced = SalesMovement::query()
            ->where('branch_id', $branchId)
            ->whereHas('movement', fn ($query) => $query->whereBetween('moved_at', [$dateFrom, $dateTo]))
            ->sum('total');

        $days = collect();
        $cursor = $dateFrom->copy()->startOfDay();
        $lastDay = $dateTo->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }
        $incomeByDay = [];
        foreach ($days as $day) {
            $incomeByDay[] = [
                'label' => $day->format('d/m'),
                'amount' => (float) SalesMovement::query()
                    ->where('branch_id', $branchId)
                    ->whereHas('movement', fn ($query) => $query->whereDate('moved_at', $day->toDateString()))
                    ->sum('total'),
            ];
        }

        $cashMovements = CashMovements::query()
            ->with('paymentConcept')
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $income = (float) $cashMovements->filter(function ($row) {
            return strtoupper((string) ($row->paymentConcept->type ?? '')) === 'I';
        })->sum('total');

        $expenses = (float) $cashMovements->filter(function ($row) {
            return strtoupper((string) ($row->paymentConcept->type ?? '')) === 'E';
        })->sum('total');

        $utility = $income - $expenses;

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
        $birthdays = Person::query()
            ->where('branch_id', $branchId)
            ->whereNotNull('fecha_nacimiento')
            ->get()
            ->filter(function ($person) use ($weekStart, $weekEnd) {
                $birthday = Carbon::parse($person->fecha_nacimiento)->year($weekStart->year);
                return $birthday->betweenIncluded($weekStart, $weekEnd);
            })
            ->values();

        $salesTodayDetails = SalesMovement::query()
            ->with(['movement.documentType', 'movement.person', 'movement.branch'])
            ->where('branch_id', $branchId)
            ->whereHas('movement', fn ($query) => $query->whereBetween('moved_at', [$dateFrom, $dateTo]))
            ->get()
            ->sortByDesc(fn ($sale) => optional(optional($sale->movement)->moved_at)->timestamp)
            ->values();

        $dashboardData = [
            'branchName' => (string) (Branch::query()->where('id', $branchId)->value('legal_name') ?? 'Sucursal actual'),
            'todayInvoiced' => (float) $todayInvoiced,
            'income' => $income,
            'expenses' => $expenses,
            'utility' => $utility,
            'birthdays' => $birthdays,
            'incomeByDay' => $incomeByDay,
            'salesTodayDetails' => $salesTodayDetails,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
        ];

        return view('pages.dashboard.ecommerce', compact('dashboardData'));
    }
}
