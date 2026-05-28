<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function index(Request $request)
    {
        $branchId = (int) $request->session()->get('branch_id');

        $metas = Meta::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('start_date')
            ->get();

        foreach ($metas as $meta) {
            $start = $meta->start_date->startOfDay();
            $end   = $meta->end_date->copy()->endOfDay();

            $meta->progress_amount = $meta->target_amount !== null
                ? (float) SalesMovement::query()
                    ->where('branch_id', $branchId)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNull('deleted_at')
                    ->sum('total')
                : null;

            $meta->progress_quantity = $meta->target_quantity !== null
                ? (float) SalesMovementDetail::query()
                    ->where('branch_id', $branchId)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNull('deleted_at')
                    ->sum('quantity')
                : null;
        }

        return view('metas.index', [
            'metas'    => $metas,
            'branchId' => $branchId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'period_type'     => ['required', 'in:week,biweek,month,custom'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
            'target_amount'   => ['nullable', 'numeric', 'min:100'],
            'target_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $branchId = (int) $request->session()->get('branch_id');
        if (! $branchId) {
            return back()->with('error', 'No hay sucursal seleccionada.');
        }

        if (empty($data['target_amount']) && empty($data['target_quantity'])) {
            return back()->withInput()->with('error', 'Debes ingresar al menos una meta (monto S/ o cantidad de unidades).');
        }

        $data['target_amount']   = $data['target_amount']   ?: null;
        $data['target_quantity'] = $data['target_quantity'] ?: null;

        Meta::create([...$data, 'branch_id' => $branchId]);

        return back()->with('status', 'Meta registrada correctamente.');
    }

    public function update(Request $request, Meta $meta)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'period_type'     => ['required', 'in:week,biweek,month,custom'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
            'target_amount'   => ['nullable', 'numeric', 'min:100'],
            'target_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($data['target_amount']) && empty($data['target_quantity'])) {
            return back()->withInput()->with('error', 'Debes ingresar al menos una meta (monto S/ o cantidad de unidades).');
        }

        $data['target_amount']   = $data['target_amount']   ?: null;
        $data['target_quantity'] = $data['target_quantity'] ?: null;

        $meta->update($data);

        return back()->with('status', 'Meta actualizada correctamente.');
    }

    public function destroy(Meta $meta)
    {
        $meta->delete();

        return back()->with('status', 'Meta eliminada.');
    }
}
