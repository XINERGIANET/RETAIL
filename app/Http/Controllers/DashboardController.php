<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashMovements;
use App\Models\Meta;
use App\Models\Person;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sessionBranchId = (int) $request->session()->get('branch_id');
        if ($sessionBranchId <= 0) {
            $sessionBranchId = (int) (optional($request->user())->person->branch_id ?? 0);
        }

        // Determine all branches accessible to this user
        $personId = $request->user()?->person_id;
        $rolePersonBranchIds = DB::table('role_person')
            ->where('person_id', $personId)
            ->pluck('branch_id')
            ->unique()
            ->values();

        $primaryBranchId = $request->user()?->person?->branch_id;
        if ($primaryBranchId && !$rolePersonBranchIds->contains($primaryBranchId)) {
            $rolePersonBranchIds->push($primaryBranchId);
        }

        $accessibleBranches = Branch::whereIn('id', $rolePersonBranchIds->toArray())
            ->orderBy('legal_name')
            ->get();

        $showBranchFilter = $accessibleBranches->count() > 1;

        // Resolve which branch IDs to query
        $branchFilter = $request->input('branch_filter', 'all');

        if (!$showBranchFilter) {
            $branchIds  = [$sessionBranchId];
            $branchFilter = (string) $sessionBranchId;
        } elseif ($branchFilter === 'all') {
            $branchIds = $accessibleBranches->pluck('id')->toArray();
        } else {
            $filterBranchId = (int) $branchFilter;
            if ($accessibleBranches->pluck('id')->contains($filterBranchId)) {
                $branchIds = [$filterBranchId];
            } else {
                $branchIds    = $accessibleBranches->pluck('id')->toArray();
                $branchFilter = 'all';
            }
        }

        $dateFrom = Carbon::parse((string) $request->input('date_from', now()->startOfWeek()->toDateString()))->startOfDay();
        $dateTo   = Carbon::parse((string) $request->input('date_to',   now()->toDateString()))->endOfDay();

        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $todayInvoiced = SalesMovement::query()
            ->whereIn('branch_id', $branchIds)
            ->whereHas('movement', fn ($q) => $q
                ->where('status', 'A')
                ->whereBetween('moved_at', [$dateFrom, $dateTo]))
            ->sum('total');

        // Income by day
        $days   = collect();
        $cursor = $dateFrom->copy()->startOfDay();
        $lastDay = $dateTo->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }
        $incomeByDay = [];
        foreach ($days as $day) {
            $incomeByDay[] = [
                'label'  => $day->format('d/m'),
                'amount' => (float) SalesMovement::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereHas('movement', fn ($q) => $q
                        ->where('status', 'A')
                        ->whereDate('moved_at', $day->toDateString()))
                    ->sum('total'),
            ];
        }

        $cashMovements = CashMovements::query()
            ->with('paymentConcept')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $income   = (float) $cashMovements->filter(fn ($r) => strtoupper((string) ($r->paymentConcept->type ?? '')) === 'I')->sum('total');
        $expenses = (float) $cashMovements->filter(fn ($r) => strtoupper((string) ($r->paymentConcept->type ?? '')) === 'E')->sum('total');
        $utility  = $income - $expenses;

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = now()->endOfWeek(Carbon::SUNDAY);

        $allBirthdays = Person::query()
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('fecha_nacimiento')
            ->get()
            ->filter(function ($person) use ($weekStart, $weekEnd) {
                $birthday = Carbon::parse($person->fecha_nacimiento)->year($weekStart->year);
                return $birthday->betweenIncluded($weekStart, $weekEnd);
            })
            ->values();

        $birthdaysClients = $allBirthdays->filter(fn ($p) => !$p->user)->values();
        $birthdaysTeam    = $allBirthdays->filter(fn ($p) =>  $p->user)->values();
        $birthdays        = $allBirthdays;

        $activeMetas = Meta::query()
            ->whereIn('branch_id', $branchIds)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date',   '>=', now()->toDateString())
            ->orderBy('end_date')
            ->get();

        foreach ($activeMetas as $meta) {
            $mStart = $meta->start_date->copy()->startOfDay();
            $mEnd   = $meta->end_date->copy()->endOfDay();

            $meta->progress_amount = $meta->target_amount !== null
                ? (float) SalesMovement::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->whereNull('deleted_at')
                    ->sum('total')
                : null;

            $meta->progress_quantity = $meta->target_quantity !== null
                ? (float) SalesMovementDetail::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->whereNull('deleted_at')
                    ->sum('quantity')
                : null;
        }

        $salesTodayDetails = SalesMovement::query()
            ->with(['movement.documentType', 'movement.person', 'movement.branch'])
            ->join('movements', 'sales_movements.movement_id', '=', 'movements.id')
            ->whereIn('sales_movements.branch_id', $branchIds)
            ->whereBetween('movements.moved_at', [$dateFrom, $dateTo])
            ->orderByDesc('movements.moved_at')
            ->select('sales_movements.*')
            ->paginate(10);

        // Label shown in header cards
        if (!$showBranchFilter || $branchFilter === 'all') {
            $filterLabel = $showBranchFilter
                ? 'Todas las sucursales'
                : ($accessibleBranches->first()?->legal_name ?? 'Sucursal actual');
        } else {
            $filterLabel = $accessibleBranches->firstWhere('id', (int) $branchFilter)?->legal_name ?? 'Sucursal';
        }

        $dashboardData = [
            'branchName'     => $filterLabel,
            'todayInvoiced'  => (float) $todayInvoiced,
            'income'         => $income,
            'expenses'       => $expenses,
            'utility'        => $utility,
            'birthdays'      => $birthdays,
            'incomeByDay'    => $incomeByDay,
            'dateFrom'       => $dateFrom->toDateString(),
            'dateTo'         => $dateTo->toDateString(),
        ];

        return view('pages.dashboard.ecommerce', compact(
            'dashboardData', 'salesTodayDetails', 'activeMetas', 'birthdaysClients', 'birthdaysTeam',
            'accessibleBranches', 'showBranchFilter', 'branchFilter'
        ));
    }
}
