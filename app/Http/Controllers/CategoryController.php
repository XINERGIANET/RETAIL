<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Operation;
use App\Models\Product;
use App\Models\ProductBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategoryController extends Controller
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

        $categories = Category::query()
            ->forBranch((int) $branchId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('description', 'ILIKE', "%{$search}%")
                        ->orWhere('abbreviation', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'search' => $search,
            'perPage' => $perPage,
            'operaciones' => $operaciones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
    
            $path = $request->file('image')->store('category', 'public');
            $data['image'] = $path;
        }

        $category = Category::create($data);
        $branchId = (int) $request->session()->get('branch_id');
        if ($branchId) {
            $category->branches()->syncWithoutDetaching([
                $branchId => [
                    'menu_type' => 'GENERAL',
                    'status' => 'E',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        $viewId = $request->input('view_id');

        return redirect()
            ->route('admin.categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Categoria creada correctamente.');
    }

    public function edit(Request $request, Category $category)
    {
        return view('categories.edit', [
            'category' => $category,
            'viewId' => $request->input('view_id'),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }


            $path = $request->file('image')->store('category', 'public');
            $data['image'] = $path;
        }
        
        $category->update($data);
        $viewId = $request->input('view_id');

        return redirect()
            ->route('admin.categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Categoria actualizada correctamente.');
    }

    public function destroy(Request $request, Category $category)
    {
        $branchId = $request->session()->get('branch_id');
        $viewId = $request->input('view_id');

        if (!$branchId) {
            return redirect()
                ->route('admin.categories.index', $viewId ? ['view_id' => $viewId] : [])
                ->with('error', 'No hay sucursal seleccionada.');
        }

        $destroyOutcome = 'full';

        DB::transaction(function () use ($category, $branchId, &$destroyOutcome) {
            $pivot = DB::table('category_branch')
                ->where('category_id', $category->id)
                ->where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->first();

            $removedThisBranch = false;
            if ($pivot) {
                DB::table('category_branch')
                    ->where('id', $pivot->id)
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
                $removedThisBranch = true;
            }

            if ($removedThisBranch) {
                $productIds = Product::query()
                    ->where('category_id', $category->id)
                    ->pluck('id');

                foreach ($productIds as $productId) {
                    $productBranch = ProductBranch::query()
                        ->where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->first();

                    if ($productBranch) {
                        $productBranch->delete();
                    }

                    $hasOtherProductBranches = ProductBranch::query()
                        ->where('product_id', $productId)
                        ->exists();

                    if ($hasOtherProductBranches) {
                        continue;
                    }

                    $product = Product::query()->find($productId);
                    if (!$product) {
                        continue;
                    }

                    if ($product->image && !empty($product->image) && Storage::disk('public')->exists($product->image)) {
                        Storage::disk('public')->delete($product->image);
                    }

                    $product->delete();
                }
            }

            $hasOtherCategoryBranches = DB::table('category_branch')
                ->where('category_id', $category->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasOtherCategoryBranches) {
                $destroyOutcome = $removedThisBranch ? 'branch_only' : 'no_op';

                return;
            }

            if ($category->image && !empty($category->image) && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $category->delete();
        });

        if ($destroyOutcome === 'branch_only') {
            $statusMessage = 'Categoría quitada de esta sucursal. Sigue existiendo en otras sedes; se eliminaron los vínculos en sede de los productos de esta categoría.';
        } elseif ($destroyOutcome === 'no_op') {
            $statusMessage = 'Esta categoría no estaba vinculada a la sucursal actual.';
        } else {
            $statusMessage = 'Categoría eliminada correctamente.';
        }

        return redirect()
            ->route('admin.categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', $statusMessage);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'file.max'      => 'El archivo no debe superar los 5 MB.',
        ]);

        $branchId = (int) $request->session()->get('branch_id');
        if (! $branchId) {
            return back()->with('error', 'No hay sucursal seleccionada.');
        }

        $viewId = $request->input('view_id');
        $path   = $request->file('file')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo. Verifica que sea un Excel válido.');
        }

        $created  = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $rowIndex => $row) {
            // Auto-detect: try column A first, fall back to B if A is empty
            $description = trim((string) ($row['A'] ?? ''));
            $abbreviation = trim((string) ($row['B'] ?? ''));

            if ($description === '') {
                $description  = trim((string) ($row['B'] ?? ''));
                $abbreviation = trim((string) ($row['C'] ?? ''));
            }

            if ($description === '') {
                continue;
            }

            // Skip header rows (any cell containing "categor")
            if (mb_stripos($description, 'categor') !== false) {
                continue;
            }

            if ($abbreviation === '') {
                $abbreviation = mb_strtoupper(mb_substr($description, 0, 5));
            }

            try {
                DB::transaction(function () use ($description, $abbreviation, $branchId, &$created, &$skipped) {
                    $existing = Category::query()
                        ->whereRaw('LOWER(description) = ?', [mb_strtolower($description)])
                        ->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId))
                        ->first();

                    if ($existing) {
                        $skipped++;
                        return;
                    }

                    $category = Category::firstOrCreate(
                        ['description' => $description],
                        ['abbreviation' => $abbreviation]
                    );

                    $category->branches()->syncWithoutDetaching([
                        $branchId => [
                            'menu_type'  => 'GENERAL',
                            'status'     => 'E',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);

                    $created++;
                });
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowIndex} (\"{$description}\"): " . $e->getMessage();
            }
        }

        $msg = "Importación completada: {$created} categoría(s) agregada(s)";
        if ($skipped > 0) {
            $msg .= ", {$skipped} omitida(s) (ya existían en esta sucursal)";
        }
        if (count($errors) > 0) {
            $msg .= '. Errores: ' . implode(' | ', $errors);
        }

        return redirect()
            ->route('admin.categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', $msg);
    }
}
