<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Operation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PromosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        $viewId = $request->input('view_id');
        $branchId = $request->session()->get('branch_id');
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $operaciones = collect();
        if ($viewId && $branchId && $profileId) {
            $operaciones = Operation::query()
                ->select('operations.*')
                ->join('branch_operation', function ($join) use ($branchId) {
                    $join->on('branch_operation.operation_id', '=', 'operations.id')
                        ->where('branch_operation.branch_id', $branchId)
                        ->where('branch_operation.status', 1)
                        ->whereNull('branch_operation.deleted_at');
                })
                ->join('operation_profile_branch', function ($join) use ($branchId, $profileId) {
                    $join->on('operation_profile_branch.operation_id', '=', 'operations.id')
                        ->where('operation_profile_branch.branch_id', $branchId)
                        ->where('operation_profile_branch.profile_id', $profileId)
                        ->where('operation_profile_branch.status', 1)
                        ->whereNull('operation_profile_branch.deleted_at');
                })
                ->where('operations.status', 1)
                ->where('operations.view_id', $viewId)
                ->whereNull('operations.deleted_at')
                ->orderBy('operations.id')
                ->distinct()
                ->get();
        }

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $promos = Promo::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $products = Product::whereHas('productBranches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->get();

        return view('promos.index', [
            'promos' => $promos,
            'search' => $search,
            'perPage' => $perPage,
            'operaciones' => $operaciones,
            'products' => $products,
        ]);
    }

    public function create(Request $request)
    {
        $branchId = $request->session()->get('branch_id');
        $products = Product::whereHas('productBranches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->get();
        
        return view('promos.create', [
            'viewId' => $request->input('view_id'),
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'end_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*' => ['exists:products,id'],
            'quantities' => ['nullable', 'array']
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('promos', 'public');
            $data['image'] = $path;
        }
        
        $data['status'] = $request->has('status') ? 1 : 0;

        DB::transaction(function() use ($data) {
            $promo = Promo::create($data);

            if (!empty($data['products'])) {
                foreach ($data['products'] as $index => $productId) {
                    $qty = $data['quantities'][$index] ?? 1;
                    $promo->products()->attach($productId, ['quantity' => $qty]);
                }
            }
        });

        $viewId = $request->input('view_id');
        return redirect()
            ->route('admin.promos.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Promo creada correctamente.');
    }

    public function edit(Request $request, Promo $promo)
    {
        $branchId = $request->session()->get('branch_id');
        $products = Product::whereHas('productBranches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->get();
        
        return view('promos.edit', [
            'promo' => $promo,
            'viewId' => $request->input('view_id'),
            'products' => $products
        ]);
    }

    public function update(Request $request, Promo $promo)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'end_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*' => ['exists:products,id'],
            'quantities' => ['nullable', 'array']
        ]);

        if ($request->hasFile('image')) {
            if ($promo->image) {
                Storage::disk('public')->delete($promo->image);
            }
            $path = $request->file('image')->store('promos', 'public');
            $data['image'] = $path;
        }

        $data['status'] = $request->has('status') ? 1 : 0;

        DB::transaction(function() use ($data, $promo) {
            $promo->update($data);

            $syncData = [];
            if (!empty($data['products'])) {
                foreach ($data['products'] as $index => $productId) {
                    $qty = $data['quantities'][$index] ?? 1;
                    $syncData[$productId] = ['quantity' => $qty];
                }
            }
            $promo->products()->sync($syncData);
        });

        $viewId = $request->input('view_id');
        return redirect()
            ->route('admin.promos.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Promo actualizada correctamente.');
    }

    public function destroy(Request $request, Promo $promo)
    {
        if ($promo->image) {
            Storage::disk('public')->delete($promo->image);
        }
        $promo->delete();
        
        $viewId = $request->input('view_id');
        return redirect()
            ->route('admin.promos.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Promo eliminada correctamente.');
    }
}
