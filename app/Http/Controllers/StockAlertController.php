<?php

namespace App\Http\Controllers;

use App\Models\StockAlert;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function index(Request $request)
    {
        $branchId = (int) $request->session()->get('branch_id');

        $alerts = StockAlert::with(['product', 'branch'])
            ->where('branch_id', $branchId)
            ->whereNull('resolved_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('stock-alerts.index', compact('alerts'));
    }
}
