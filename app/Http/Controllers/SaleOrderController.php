<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Card;
use App\Models\CashMovements;
use App\Models\CashRegister;
use App\Models\CashShiftRelation;
use App\Models\DigitalWallet;
use App\Models\DocumentType;
use App\Models\Kardex;
use App\Models\Location;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\PaymentConcept;
use App\Models\PaymentGateways;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\SaleOrderPayment;
use App\Models\SaleOrderReturn;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use App\Models\Shift;
use App\Models\Unit;
use App\Services\KardexSyncService;
use App\Services\StockAlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleOrderController extends Controller
{
    // ── Vistas ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $branchId = (int) session('branch_id');
        $search   = $request->input('search');
        $status   = $request->input('status', 'all');
        $perPage  = in_array((int) $request->input('per_page', 10), [10, 20, 50, 100], true)
            ? (int) $request->input('per_page', 10)
            : 10;

        $baseQuery = SaleOrder::query()
            ->where('branch_id', $branchId)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'ILIKE', "%{$search}%")
                        ->orWhere('person_name', 'ILIKE', "%{$search}%");
                });
            });

        $filteredTotal = (clone $baseQuery)->sum('total');

        $orders = $baseQuery
            ->with(['person', 'createdBy', 'delivery'])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('sale-orders.index', [
            'orders'        => $orders,
            'search'        => $search,
            'status'        => $status,
            'perPage'       => $perPage,
            'filteredTotal' => $filteredTotal,
        ]);
    }

    public function create()
    {
        $branchId = (int) session('branch_id');

        $products = Product::query()
            ->where('type', 'SELLABLE')
            ->whereHas('productBranches', fn ($q) => $q->where('branch_id', $branchId))
            ->with('category')
            ->orderBy('description')
            ->get()
            ->map(fn ($p) => [
                'id'       => (int) $p->id,
                'code'     => (string) ($p->code ?? ''),
                'name'     => $p->description,
                'category' => $p->category?->description ?? 'Sin categoría',
                'image'       => $p->image ? asset('storage/' . ltrim((string) $p->image, '/')) : null,
                'is_favorite' => (bool) $p->is_favorite,
            ])
            ->values();

        $productBranches = ProductBranch::query()
            ->where('branch_id', $branchId)
            ->with('taxRate')
            ->get()
            ->map(fn ($pb) => [
                'product_id' => (int) $pb->product_id,
                'price'      => (float) $pb->price,
                'stock'      => (float) ($pb->stock ?? 0),
            ])
            ->values();

        $people = Person::query()
            ->where('branch_id', $branchId)
            ->whereHas('roles', fn ($q) => $q->where('roles.id', 3)->where('role_person.branch_id', $branchId))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'document_number']);

        $defaultClientId = Person::query()
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereRaw('UPPER(first_name) = ?', ['CLIENTES'])
                          ->whereRaw('UPPER(last_name) = ?', ['VARIOS']);
                })->orWhere(function ($inner) {
                    $inner->whereRaw('UPPER(first_name) = ?', ['CLIENTES VARIOS'])
                          ->where(function ($i2) {
                              $i2->whereNull('last_name')->orWhere('last_name', '');
                          });
                });
            })
            ->value('id');

        $paymentMethods  = PaymentMethod::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);
        $paymentGateways = PaymentGateways::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);
        $cards           = Card::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'type', 'icon', 'order_num']);
        $digitalWallets  = DigitalWallet::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);
        $units           = Unit::query()->orderBy('description')->get(['id', 'description']);

        // Datos de ubicación para el modal de cliente rápido
        $branch = Branch::query()->with('location.parent.parent')->find($branchId);

        $departments = Location::query()->where('type', 'department')->orderBy('name')->get(['id', 'name']);
        $provinces   = Location::query()->where('type', 'province')->orderBy('name')->get(['id', 'name', 'parent_location_id']);
        $districts   = Location::query()->where('type', 'district')->orderBy('name')->get(['id', 'name', 'parent_location_id']);

        $branchDistrict    = $branch?->location;
        $branchProvince    = $branchDistrict?->parent;
        $branchDepartment  = $branchProvince?->parent;

        $selectedDistrictId   = $branch?->location_id;
        $selectedProvinceId   = $branchDistrict?->parent_location_id;
        $selectedDepartmentId = $branchProvince?->parent_location_id;

        $quickClientStoreUrl = route('admin.sales.clients.store');

        $openCashRegisters    = $this->getOpenCashRegisters($branchId);
        $defaultCashRegisterId = $openCashRegisters->first()?->id;

        return view('sale-orders.create', compact(
            'products', 'productBranches', 'people', 'defaultClientId',
            'paymentMethods', 'paymentGateways', 'cards', 'digitalWallets', 'units',
            'departments', 'provinces', 'districts',
            'selectedDepartmentId', 'selectedProvinceId', 'selectedDistrictId',
            'quickClientStoreUrl', 'openCashRegisters', 'defaultCashRegisterId'
        ));
    }

    public function show(SaleOrder $saleOrder)
    {
        abort_if($saleOrder->branch_id !== (int) session('branch_id'), 403);

        $saleOrder->loadMissing([
            'items.product',
            'payments',
            'returns.saleOrderItem.product',
            'person',
            'movement',
            'delivery',
        ]);

        $paymentMethods  = PaymentMethod::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);
        $paymentGateways = PaymentGateways::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);
        $cards           = Card::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'type', 'icon', 'order_num']);
        $digitalWallets  = DigitalWallet::query()->where('status', true)->orderBy('order_num')->get(['id', 'description', 'order_num']);

        $documentTypes = DocumentType::query()
            ->where('movement_type_id', 2)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cashRegisters = CashRegister::query()
            ->where('branch_id', $saleOrder->branch_id)
            ->whereIn('status', ['1', 'A', 'active'])
            ->orderBy('number')
            ->get(['id', 'number', 'series']);

        $openCashRegisters    = $this->getOpenCashRegisters($saleOrder->branch_id);
        $defaultCashRegisterId = $openCashRegisters->first()?->id;

        return view('sale-orders.show', compact(
            'saleOrder', 'paymentMethods', 'paymentGateways', 'cards',
            'digitalWallets', 'documentTypes', 'cashRegisters',
            'openCashRegisters', 'defaultCashRegisterId'
        ));
    }

    public function printTicket(SaleOrder $saleOrder)
    {
        abort_if($saleOrder->branch_id !== (int) session('branch_id'), 403);
        $saleOrder->loadMissing(['items.product', 'payments', 'person', 'branch']);
        return view('sale-orders.ticket', compact('saleOrder'));
    }

    // ── Acciones JSON ────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $branchId = (int) session('branch_id');
        if (!$branchId) {
            return response()->json(['success' => false, 'message' => 'No se pudo determinar la sucursal.'], 422);
        }

        $validated = $request->validate([
            'items'                           => 'required|array|min:1',
            'items.*.product_id'              => 'required|integer|exists:products,id',
            'items.*.quantity'                => 'required|numeric|min:0.000001',
            'items.*.unit_price'              => 'required|numeric|min:0',
            'person_id'                       => 'nullable|integer|exists:people,id',
            'due_date'                        => 'nullable|date',
            'notes'                           => 'nullable|string|max:65535',
            'currency'                        => 'nullable|string|max:10',
            'exchange_rate'                   => 'nullable|numeric|min:0',
            // Pago inicial opcional
            'initial_payment.amount'            => 'nullable|numeric|min:1',
            'initial_payment.payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'initial_payment.digital_wallet_id' => 'nullable|integer|exists:digital_wallets,id',
            'initial_payment.card_id'           => 'nullable|integer|exists:cards,id',
            'initial_payment.payment_gateway_id'=> 'nullable|integer|exists:payment_gateways,id',
            'initial_payment.cash_register_id'  => 'nullable|integer|exists:cash_registers,id',
            'initial_payment.reference'         => 'nullable|string|max:100',
            'initial_payment.notes'             => 'nullable|string|max:65535',
            'delivery_type'                     => 'nullable|string|in:immediate,shipping',
        ]);

        try {
            DB::beginTransaction();

            $user   = $request->user();
            $person = $validated['person_id'] ? Person::find($validated['person_id']) : null;

            $total = collect($validated['items'])->sum(
                fn ($i) => round((float) $i['quantity'] * (float) $i['unit_price'], 6)
            );

            $saleOrder = SaleOrder::create([
                'number'          => $this->generateOrderNumber($branchId),
                'status'          => 'draft',
                'currency'        => $validated['currency'] ?? 'PEN',
                'exchange_rate'   => $validated['exchange_rate'] ?? 1,
                'total'           => $total,
                'paid'            => 0,
                'due_date'        => $validated['due_date'] ?? null,
                'notes'           => $validated['notes'] ?? null,
                'branch_id'       => $branchId,
                'person_id'       => $person?->id,
                'person_name'     => $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : null,
                'created_by'      => $user?->id,
                'created_by_name' => $user?->name,
            ]);

            $orderAlertPairs = [];
            foreach ($validated['items'] as $row) {
                $productId = (int) $row['product_id'];
                $quantity  = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];

                $product = Product::with('baseUnit')->findOrFail($productId);

                $productBranch = ProductBranch::query()
                    ->where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->first();

                if (!$productBranch) {
                    throw new \Exception("Producto {$product->description} no disponible en esta sucursal.");
                }

                $availableStock = (float) ($productBranch->stock ?? 0);
                if ($availableStock < $quantity) {
                    throw new \Exception(
                        "Stock insuficiente para \"{$product->description}\". Disponible: " . (int) $availableStock . ", solicitado: " . (int) $quantity . "."
                    );
                }

                $item = SaleOrderItem::create([
                    'sale_order_id'    => $saleOrder->id,
                    'product_id'       => $productId,
                    'product_snapshot' => [
                        'id'          => $product->id,
                        'code'        => $product->code,
                        'description' => $product->description,
                    ],
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => round($quantity * $unitPrice, 6),
                    'returned_qty' => 0,
                ]);

                $productBranch->update([
                    'stock' => (float) ($productBranch->stock ?? 0) - $quantity,
                ]);
                $orderAlertPairs[] = ['product_id' => $productId, 'branch_id' => $branchId];

                $unitId = (int) ($product->baseUnit?->id ?? Unit::query()->value('id'));
                $this->writeKardexEntry($saleOrder, $item, $productId, $unitId, -$quantity, $unitPrice);
            }

            // Pago inicial opcional — crea Nota de Venta si se proporcionó
            $initialPayment = $validated['initial_payment'] ?? null;
            if (!empty($initialPayment['amount']) && !empty($initialPayment['payment_method_id'])) {
                $advancePayment = $this->recordPayment($saleOrder, $initialPayment, $user);
                $this->createNoteOfSaleEntry($saleOrder, (float) $initialPayment['amount'], $user, [$advancePayment]);
            }

            // Registro de entrega
            $deliveryType = $validated['delivery_type'] ?? 'immediate';
            $deliveryStatus = $deliveryType === 'immediate' ? 'ENTREGADO' : 'EN_PROCESO';
            $saleOrder->delivery()->create([
                'status'       => $deliveryStatus,
                'delivered_at' => $deliveryType === 'immediate' ? now() : null,
                'delivered_by' => $user?->id,
            ]);

            DB::commit();

            app(StockAlertService::class)->evaluateMany($orderAlertPairs);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente.',
                'data'    => [
                    'id'      => $saleOrder->id,
                    'number'  => $saleOrder->number,
                    'total'   => (float) $saleOrder->total,
                    'paid'    => (float) $saleOrder->paid,
                    'balance' => (float) $saleOrder->total - (float) $saleOrder->paid,
                    'status'  => $saleOrder->status,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SaleOrderController@store', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error al crear el pedido.',
            ], 500);
        }
    }

    public function addPayment(Request $request, SaleOrder $saleOrder)
    {
        abort_if($saleOrder->branch_id !== (int) session('branch_id'), 403);
        abort_if(
            in_array($saleOrder->status, ['completed', 'cancelled']),
            422,
            'No se puede registrar pagos en este estado.'
        );

        $validated = $request->validate([
            'methods'                          => 'required|array|min:1|max:4',
            'methods.*.payment_method_id'      => 'required|integer|exists:payment_methods,id',
            'methods.*.amount'                 => 'required|numeric|min:1',
            'methods.*.reference'              => 'nullable|string|max:100',
            'methods.*.card_id'                => 'nullable|integer|exists:cards,id',
            'methods.*.digital_wallet_id'      => 'nullable|integer|exists:digital_wallets,id',
            'cash_register_id'                 => 'nullable|integer|exists:cash_registers,id',
            'paid_at'                          => 'nullable|date',
            'notes'                            => 'nullable|string|max:65535',
            'generate_invoice'                 => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $saleOrder->lockForUpdate();

            $totalAmount      = collect($validated['methods'])->sum('amount');
            $remainingBalance = round((float) $saleOrder->total - (float) $saleOrder->paid, 2);

            if ($totalAmount > $remainingBalance + 0.01) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "El total a pagar (S/ " . number_format($totalAmount, 2) . ") supera el saldo pendiente (S/ " . number_format($remainingBalance, 2) . ").",
                ], 422);
            }

            $batchPayments = [];
            foreach ($validated['methods'] as $methodData) {
                $batchPayments[] = $this->recordPayment($saleOrder, array_merge($methodData, [
                    'cash_register_id' => $validated['cash_register_id'] ?? null,
                    'paid_at'          => $validated['paid_at'] ?? null,
                    'notes'            => $validated['notes'] ?? null,
                ]), $request->user());
            }

            // Crear Nota de Venta vinculando los movimientos de caja del batch
            $this->createNoteOfSaleEntry($saleOrder, $totalAmount, $request->user(), $batchPayments);

            // Generar comprobante formal si el usuario lo solicitó y el pedido quedó saldado
            $invoiced = false;
            if (!empty($validated['generate_invoice']) && $saleOrder->status === 'completed' && is_null($saleOrder->movement_id)) {
                $docTypeId = $this->resolveDefaultDocumentTypeId($saleOrder->branch_id);
                $openReg   = $this->getOpenCashRegisters($saleOrder->branch_id)->first();
                if ($docTypeId && $openReg) {
                    $this->executeInvoice(
                        $saleOrder,
                        DocumentType::findOrFail($docTypeId),
                        $openReg,
                        $request->user()
                    );
                    $invoiced = true;
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => $invoiced
                    ? 'Pago registrado y comprobante generado.'
                    : 'Pago registrado correctamente.',
                'invoiced' => $invoiced,
                'data'     => [
                    'paid'        => (float) $saleOrder->paid,
                    'balance'     => (float) $saleOrder->total - (float) $saleOrder->paid,
                    'status'      => $saleOrder->status,
                    'movement_id' => $saleOrder->movement_id,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SaleOrderController@addPayment', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error al registrar el pago.',
            ], 500);
        }
    }

    public function addReturn(Request $request, SaleOrder $saleOrder)
    {
        $branchId = (int) session('branch_id');
        abort_if($saleOrder->branch_id !== $branchId, 403);
        abort_if(
            in_array($saleOrder->status, ['cancelled']),
            422,
            'No se puede registrar devoluciones en este estado.'
        );

        $validated = $request->validate([
            'sale_order_item_id' => 'required|integer|exists:sale_order_items,id',
            'quantity'           => 'required|numeric|min:0.000001',
            'notes'              => 'nullable|string|max:65535',
        ]);

        try {
            DB::beginTransaction();

            $item = SaleOrderItem::query()
                ->where('id', $validated['sale_order_item_id'])
                ->where('sale_order_id', $saleOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            $activeQty   = (float) $item->quantity - (float) $item->returned_qty;
            $returnQty   = (float) $validated['quantity'];
            $maxReturnable = $this->resolveReturnableQty($saleOrder, $item, $activeQty);

            if ($returnQty > $activeQty + 0.000001) {
                throw new \Exception("La cantidad a devolver ({$returnQty}) supera la cantidad activa ({$activeQty}).");
            }
            if ($returnQty > $maxReturnable + 0.000001) {
                throw new \Exception("No se puede devolver {$returnQty} unid. El pago registrado cubre esas unidades (máx. devolvible: {$maxReturnable}).");
            }

            $user = $request->user();
            $returnSubtotal = round($returnQty * (float) $item->unit_price, 6);

            SaleOrderReturn::create([
                'sale_order_id'      => $saleOrder->id,
                'sale_order_item_id' => $item->id,
                'quantity'           => $returnQty,
                'unit_price'         => $item->unit_price,
                'subtotal'           => $returnSubtotal,
                'returned_at'        => now(),
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => $user?->id,
                'created_by_name'    => $user?->name,
            ]);

            $item->returned_qty = (float) $item->returned_qty + $returnQty;
            $item->save();

            // Restituir stock
            $productBranch = ProductBranch::query()
                ->where('product_id', $item->product_id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($productBranch) {
                $productBranch->stock = (float) ($productBranch->stock ?? 0) + $returnQty;
                $productBranch->save();
            }

            // Kardex: entrada positiva (devolución)
            $unitId = (int) (
                Product::with('baseUnit')->find($item->product_id)?->baseUnit?->id
                ?? Unit::query()->value('id')
            );
            $this->writeKardexEntry($saleOrder, $item, (int) $item->product_id, $unitId, $returnQty, (float) $item->unit_price);

            // Restar del total y recalcular estado
            $saleOrder->lockForUpdate();
            $saleOrder->total = round((float) $saleOrder->total - $returnSubtotal, 6);
            $this->recalculateStatus($saleOrder);
            $saleOrder->save();

            DB::commit();

            if ($productBranch) {
                app(StockAlertService::class)->evaluate((int) $item->product_id, $branchId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Devolución registrada correctamente.',
                'data'    => [
                    'total'   => (float) $saleOrder->total,
                    'paid'    => (float) $saleOrder->paid,
                    'balance' => (float) $saleOrder->total - (float) $saleOrder->paid,
                    'status'  => $saleOrder->status,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SaleOrderController@addReturn', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error al registrar la devolución.',
            ], 500);
        }
    }

    public function updateDelivery(Request $request, SaleOrder $saleOrder)
    {
        abort_if($saleOrder->branch_id !== (int) session('branch_id'), 403);
        abort_if($saleOrder->status === 'cancelled', 422, 'No se puede actualizar la entrega de un pedido cancelado.');

        $validated = $request->validate([
            'delivery_status' => 'required|string|in:PENDIENTE,ENTREGADO',
        ]);

        if (
            $validated['delivery_status'] === 'PENDIENTE'
            && $saleOrder->delivery?->status === 'ENTREGADO'
            && $saleOrder->status === 'completed'
        ) {
            abort(422, 'No se puede revertir la entrega de un pedido que ya está pagado en su totalidad.');
        }

        $deliveredAt = $validated['delivery_status'] === 'ENTREGADO' ? now() : null;
        $deliveryData = [
            'status'       => $validated['delivery_status'],
            'delivered_at' => $deliveredAt,
            'delivered_by' => $request->user()->id,
        ];

        // SaleOrder es el dueño del registro — Movement lo lee desde acá
        $saleOrder->delivery()->updateOrCreate([], $deliveryData);

        return response()->json([
            'success'         => true,
            'message'         => $validated['delivery_status'] === 'ENTREGADO' ? 'Pedido marcado como entregado.' : 'Pedido marcado como pendiente de entrega.',
            'delivery_status' => $validated['delivery_status'],
        ]);
    }

    public function cancel(Request $request, SaleOrder $saleOrder)
    {
        $branchId = (int) session('branch_id');
        abort_if($saleOrder->branch_id !== $branchId, 403);
        abort_if($saleOrder->movement_id !== null, 422, 'No se puede cancelar un pedido ya facturado.');
        abort_if($saleOrder->status === 'cancelled', 422, 'El pedido ya está cancelado.');

        try {
            DB::beginTransaction();

            $cancelAlertPairs = [];
            foreach ($saleOrder->items()->get() as $item) {
                $activeQty = (float) $item->quantity - (float) $item->returned_qty;
                if ($activeQty <= 0) {
                    continue;
                }

                $productBranch = ProductBranch::query()
                    ->where('product_id', $item->product_id)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->first();

                if ($productBranch) {
                    $productBranch->stock = (float) ($productBranch->stock ?? 0) + $activeQty;
                    $productBranch->save();
                    $cancelAlertPairs[] = ['product_id' => (int) $item->product_id, 'branch_id' => $branchId];
                }
            }

            Kardex::query()->where('sale_order_id', $saleOrder->id)->delete();

            $saleOrder->status = 'cancelled';
            $saleOrder->save();

            DB::commit();

            app(StockAlertService::class)->evaluateMany($cancelAlertPairs);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado correctamente.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SaleOrderController@cancel', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error al cancelar el pedido.',
            ], 500);
        }
    }

    public function invoice(Request $request, SaleOrder $saleOrder)
    {
        $branchId = (int) session('branch_id');
        abort_if($saleOrder->branch_id !== $branchId, 403);
        abort_if($saleOrder->movement_id !== null, 422, 'Este pedido ya fue facturado.');
        abort_if($saleOrder->status === 'cancelled', 422, 'No se puede facturar un pedido cancelado.');

        $balance = (float) $saleOrder->total - (float) $saleOrder->paid;
        if (abs($balance) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => "El pedido tiene saldo pendiente ({$balance}). Registre todos los pagos antes de facturar.",
            ], 422);
        }

        $validated = $request->validate([
            'document_type_id'  => 'required|integer|exists:document_types,id',
            'cash_register_id'  => 'required|integer|exists:cash_registers,id',
            'billing_status'    => 'nullable|string|in:PENDING,INVOICED,NOT_APPLICABLE',
            'invoice_series'    => 'nullable|string|max:20',
            'invoice_number'    => 'nullable|string|max:50',
            'moved_at'          => 'nullable|string|max:32',
        ]);

        try {
            DB::beginTransaction();

            $user         = $request->user();
            $documentType = DocumentType::findOrFail($validated['document_type_id']);
            $cashRegister = CashRegister::query()
                ->where('id', $validated['cash_register_id'])
                ->where('branch_id', $branchId)
                ->firstOrFail();

            $movedAt = now();
            if (!empty($validated['moved_at'])) {
                try { $movedAt = Carbon::parse($validated['moved_at']); } catch (\Throwable) {}
            }

            $movement = $this->executeInvoice(
                $saleOrder, $documentType, $cashRegister, $user, $movedAt,
                $validated['billing_status'] ?? null,
                $validated['invoice_series']  ?? null,
                $validated['invoice_number']  ?? null,
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido facturado correctamente.',
                'data'    => [
                    'movement_id'   => $movement->id,
                    'number'        => $movement->number,
                    'sale_order_id' => $saleOrder->id,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SaleOrderController@invoice', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error al facturar el pedido.',
            ], 500);
        }
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function generateOrderNumber(int $branchId): string
    {
        // PostgreSQL no permite FOR UPDATE con MAX(). Se usa ORDER BY id DESC LIMIT 1.
        $last = SaleOrder::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('number');

        return str_pad((string) ((int) $last + 1), 8, '0', STR_PAD_LEFT);
    }

    private function generateSaleMovementNumber(int $branchId, int $documentTypeId, int $cashRegisterId): string
    {
        $numbers = Movement::query()
            ->where('branch_id', $branchId)
            ->where('document_type_id', $documentTypeId)
            ->lockForUpdate()
            ->pluck('number');

        $last = 0;
        foreach ($numbers as $num) {
            $raw = trim((string) $num);
            if (preg_match('/^\d+$/', $raw) && (int) $raw > $last) {
                $last = (int) $raw;
            }
        }

        return str_pad((string) ($last + 1), 8, '0', STR_PAD_LEFT);
    }

    private function generateCashMovementNumber(int $branchId, int $cashRegisterId): string
    {
        $last = Movement::query()
            ->select('movements.number')
            ->join('cash_movements', 'cash_movements.movement_id', '=', 'movements.id')
            ->where('movements.branch_id', $branchId)
            ->where('cash_movements.cash_register_id', $cashRegisterId)
            ->lockForUpdate()
            ->orderByDesc('movements.number')
            ->value('number');

        return str_pad((string) ((int) $last + 1), 8, '0', STR_PAD_LEFT);
    }

    private function recordPayment(SaleOrder $saleOrder, array $data, $user): SaleOrderPayment
    {
        $paymentMethod  = PaymentMethod::findOrFail((int) $data['payment_method_id']);
        $digitalWallet  = !empty($data['digital_wallet_id']) ? DigitalWallet::find((int) $data['digital_wallet_id']) : null;
        $card           = !empty($data['card_id']) ? Card::find((int) $data['card_id']) : null;
        $paymentGateway = !empty($data['payment_gateway_id']) ? PaymentGateways::find((int) $data['payment_gateway_id']) : null;

        $payment = SaleOrderPayment::create([
            'sale_order_id'      => $saleOrder->id,
            'amount'             => $data['amount'],
            'payment_method_id'  => $paymentMethod->id,
            'payment_method'     => $paymentMethod->description,
            'digital_wallet_id'  => $digitalWallet?->id,
            'digital_wallet'     => $digitalWallet?->description,
            'card_id'            => $card?->id,
            'card'               => $card?->description,
            'payment_gateway_id' => $paymentGateway?->id,
            'payment_gateway'    => $paymentGateway?->description,
            'reference'          => $data['reference'] ?? null,
            'paid_at'            => !empty($data['paid_at']) ? $data['paid_at'] : now(),
            'notes'              => $data['notes'] ?? null,
            'created_by'         => $user?->id,
            'created_by_name'    => $user?->name,
        ]);

        // Registrar en caja inmediatamente si se indicó la caja
        if (!empty($data['cash_register_id'])) {
            $cashMovementId = $this->createInstantCashEntry($saleOrder, $payment, (int) $data['cash_register_id'], $user);
            if ($cashMovementId) {
                $payment->cash_movement_id = $cashMovementId;
                $payment->save();
            }
        }

        $saleOrder->paid = SaleOrderPayment::where('sale_order_id', $saleOrder->id)->sum('amount');
        $this->recalculateStatus($saleOrder);
        $saleOrder->save();

        return $payment;
    }

    private function writeKardexEntry(
        SaleOrder $saleOrder,
        SaleOrderItem $item,
        int $productId,
        int $unitId,
        float $quantity,
        float $unitPrice
    ): void {
        $lastStock = (float) (Kardex::query()
            ->where('producto_id', $productId)
            ->where('sucursal_id', $saleOrder->branch_id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->value('stockactual') ?? 0);

        Kardex::create([
            'detalle_id'       => $item->id,
            'producto_id'      => $productId,
            'unidad_id'        => $unitId,
            'cantidad'         => $quantity,
            'preciounitario'   => $unitPrice,
            'moneda'           => $saleOrder->currency ?? 'PEN',
            'tipocambio'       => $saleOrder->exchange_rate ?? 1,
            'total'            => round(abs($quantity) * $unitPrice, 6),
            'fecha'            => now(),
            'situacion'        => $quantity < 0 ? 'S' : 'E',
            'usuario_id'       => $saleOrder->created_by,
            'usuario'          => $saleOrder->created_by_name ?? 'Sistema',
            'movimiento_id'    => null,
            'sale_order_id'    => $saleOrder->id,
            'tipomovimiento_id'=> $this->resolveSaleOrderMovementTypeId(),
            'tipodocumento_id' => $this->resolveSaleOrderDocumentTypeId(),
            'sucursal_id'      => $saleOrder->branch_id,
            'stockanterior'    => $lastStock,
            'stockactual'      => round($lastStock + $quantity, 6),
        ]);
    }

    private function resolveSaleOrderMovementTypeId(): int
    {
        $id = MovementType::query()
            ->where('description', 'ILIKE', '%pedido%')
            ->orderBy('id')
            ->value('id')
            ?? MovementType::query()
                ->where('description', 'ILIKE', '%venta%')
                ->orderBy('id')
                ->value('id')
            ?? MovementType::query()->orderBy('id')->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de movimiento para el pedido de venta.');
        }

        return (int) $id;
    }

    private function resolveSaleOrderDocumentTypeId(): int
    {
        $movementTypeId = $this->resolveSaleOrderMovementTypeId();

        $id = DocumentType::query()
            ->where('movement_type_id', $movementTypeId)
            ->where('name', 'ILIKE', '%pedido%')
            ->orderBy('id')
            ->value('id')
            ?? DocumentType::query()
                ->where('movement_type_id', $movementTypeId)
                ->orderBy('id')
                ->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de documento para el pedido de venta.');
        }

        return (int) $id;
    }

    private function resolveReturnableQty(SaleOrder $saleOrder, SaleOrderItem $targetItem, float $activeQty): float
    {
        $paidRemaining = (float) $saleOrder->paid;

        foreach ($saleOrder->items()->orderBy('id')->get() as $item) {
            $itemActiveQty = max(0.0, (float)$item->quantity - (float)$item->returned_qty);
            $unitPrice     = (float) $item->unit_price;

            if ($itemActiveQty <= 0 || $unitPrice <= 0) {
                if ($item->id === $targetItem->id) return 0.0;
                continue;
            }

            if ($paidRemaining <= 0) {
                if ($item->id === $targetItem->id) return $itemActiveQty;
                continue;
            }

            $coveredUnits  = (int) ceil(min($paidRemaining, $unitPrice * $itemActiveQty) / $unitPrice);
            $returnable    = max(0.0, $itemActiveQty - $coveredUnits);
            $paidRemaining = max(0.0, $paidRemaining - $unitPrice * $coveredUnits);

            if ($item->id === $targetItem->id) return $returnable;
        }

        return 0.0;
    }

    private function recalculateStatus(SaleOrder $saleOrder): void
    {
        if ($saleOrder->status === 'cancelled') {
            return;
        }
        $balance = (float) $saleOrder->total - (float) $saleOrder->paid;
        if ($balance <= 0.001) {
            $saleOrder->status = 'completed';
        } elseif ((float) $saleOrder->paid > 0) {
            $saleOrder->status = 'partial';
        } else {
            $saleOrder->status = 'draft';
        }
    }

    private function resolveCashMovementTypeId(): int
    {
        $id = MovementType::query()
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%caja%')->orWhere('description', 'ILIKE', '%cash%'))
            ->orderBy('id')
            ->value('id') ?? MovementType::find(4)?->id ?? MovementType::query()->orderBy('id')->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de movimiento de caja.');
        }

        return (int) $id;
    }

    private function resolveCashIncomeDocumentTypeId(int $cashMovementTypeId): int
    {
        $id = DocumentType::query()
            ->where('movement_type_id', $cashMovementTypeId)
            ->where('name', 'ILIKE', '%ingreso%')
            ->orderBy('id')
            ->value('id')
            ?? DocumentType::query()
                ->where('movement_type_id', $cashMovementTypeId)
                ->orderBy('id')
                ->value('id');

        if (!$id) {
            throw new \Exception('No se encontró tipo de documento de ingreso de caja.');
        }

        return (int) $id;
    }

    private function resolvePaymentConcept(): PaymentConcept
    {
        $concept = PaymentConcept::query()
            ->where('type', 'I')
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%cliente%')->orWhere('description', 'ILIKE', '%venta%'))
            ->orderBy('id')
            ->first()
            ?? PaymentConcept::query()->where('type', 'I')->orderBy('id')->first();

        if (!$concept) {
            throw new \Exception('No se encontró concepto de pago de ingreso.');
        }

        return $concept;
    }

    private function resolveNotaVentaDocumentTypeId(): ?int
    {
        // Busca primero tipos informales (nota de venta, ticket, vale) para el comprobante parcial.
        // Si no existe ninguno, usa el primer tipo de venta disponible.
        return DocumentType::query()
            ->where('movement_type_id', 2)
            ->where(function ($q) {
                $q->where('name', 'ILIKE', '%nota%venta%')
                  ->orWhere('name', 'ILIKE', '%ticket%')
                  ->orWhere('name', 'ILIKE', '%vale%')
                  ->orWhere('name', 'ILIKE', '%nota%');
            })
            ->orderBy('id')
            ->value('id')
            ?? DocumentType::query()
                ->where('movement_type_id', 2)
                ->orderBy('id')
                ->value('id');
    }

    private function createNoteOfSaleEntry(SaleOrder $saleOrder, float $amount, $user, array $batchPayments = []): ?int
    {
        $docTypeId = $this->resolveNotaVentaDocumentTypeId();
        if (!$docTypeId) {
            return null;
        }

        $branchId = $saleOrder->branch_id;
        $branch   = Branch::findOrFail($branchId);
        $shift    = Shift::where('branch_id', $branchId)->first() ?? Shift::first();
        if (!$shift) {
            return null;
        }

        $movementType = MovementType::query()
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%venta%')->orWhere('description', 'ILIKE', '%sale%'))
            ->orWhere('id', 2)
            ->orderBy('id')
            ->first()
            ?? MovementType::first();

        $number = $this->generateSaleMovementNumber($branchId, $docTypeId, 0);

        $movement = Movement::create([
            'number'            => $number,
            'moved_at'          => now(),
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Pago de pedido #' . $saleOrder->number,
            'status'            => 'A',
            'movement_type_id'  => $movementType->id,
            'document_type_id'  => $docTypeId,
            'branch_id'         => $branchId,
            'parent_movement_id'=> null,
            'shift_id'          => $shift->id,
            'shift_snapshot'    => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
        ]);

        $salesMovement = SalesMovement::create([
            'branch_snapshot' => ['id' => $branch->id, 'legal_name' => $branch->legal_name],
            'series'          => '001',
            'billing_status'  => 'NOT_APPLICABLE',
            'billing_number'  => null,
            'year'            => Carbon::now()->year,
            'detail_type'     => 'DETALLADO',
            'consumption'     => 'N',
            'payment_type'    => 'CONTADO',
            'status'          => 'N',
            'sale_type'       => 'MINORISTA',
            'currency'        => $saleOrder->currency ?? 'PEN',
            'exchange_rate'   => $saleOrder->exchange_rate ?? 1,
            'subtotal'        => round($amount / 1.18, 6),
            'tax'             => round($amount - ($amount / 1.18), 6),
            'total'           => $amount,
            'movement_id'     => $movement->id,
            'branch_id'       => $branchId,
        ]);

        $defaultUnitId = Unit::query()->value('id');
        SalesMovementDetail::create([
            'detail_type'         => 'DETALLADO',
            'sales_movement_id'   => $salesMovement->id,
            'code'                => '',
            'description'         => 'Pago de pedido #' . $saleOrder->number,
            'product_id'          => null,
            'product_snapshot'    => null,
            'unit_id'             => $defaultUnitId,
            'tax_rate_id'         => null,
            'tax_rate_snapshot'   => null,
            'quantity'            => 1,
            'amount'              => $amount,
            'discount_percentage' => 0,
            'original_amount'     => round($amount / 1.18, 6),
            'comment'             => null,
            'parent_detail_id'    => null,
            'complements'         => [],
            'status'              => 'A',
            'branch_id'           => $branchId,
        ]);

        // Vincular los movimientos de caja del batch al movimiento Nota de Venta,
        // para que el filtro por caja del SalesController los encuentre.
        $cashMovementIds = collect($batchPayments)
            ->pluck('cash_movement_id')
            ->filter()
            ->values();
        if ($cashMovementIds->isNotEmpty()) {
            $cashEntryMvtIds = CashMovements::whereIn('id', $cashMovementIds)->pluck('movement_id');
            Movement::whereIn('id', $cashEntryMvtIds)->update(['parent_movement_id' => $movement->id]);
        }

        $noteIds   = $saleOrder->note_movement_ids ?? [];
        $noteIds[] = $movement->id;
        $saleOrder->note_movement_ids = $noteIds;
        $saleOrder->save();

        return $movement->id;
    }

    private function executeInvoice(
        SaleOrder    $saleOrder,
        DocumentType $documentType,
        CashRegister $cashRegister,
        $user,
        ?Carbon $movedAt       = null,
        ?string $billingStatus = null,
        ?string $invoiceSeries = null,
        ?string $invoiceNumber = null
    ): Movement {
        $branchId = $saleOrder->branch_id;
        $movedAt  = $movedAt ?? now();
        $branch   = Branch::findOrFail($branchId);

        $shift = Shift::where('branch_id', $branchId)->first() ?? Shift::first();
        if (!$shift) {
            throw new \Exception('No hay turno disponible para facturar.');
        }

        $movementType = MovementType::query()
            ->where(fn ($q) => $q->where('description', 'ILIKE', '%venta%')->orWhere('description', 'ILIKE', '%sale%'))
            ->orWhere('id', 2)
            ->orderBy('id')
            ->first()
            ?? MovementType::first();

        // Anular Notas de Venta previas de este pedido
        $noteMovementIds = $saleOrder->note_movement_ids ?? [];
        if (!empty($noteMovementIds)) {
            Movement::whereIn('id', $noteMovementIds)->update(['status' => 'N']);
        }

        $billingStatus = $billingStatus
            ?? ($documentType->name && str_contains(strtolower($documentType->name), 'factura') ? 'PENDING' : 'NOT_APPLICABLE');
        $invoiceSeries = trim((string) ($invoiceSeries ?? ($cashRegister->series ?: '001')));
        $invoiceNumber = ($billingStatus === 'INVOICED') ? trim((string) ($invoiceNumber ?? '')) : null;

        $number = $this->generateSaleMovementNumber($branchId, (int) $documentType->id, (int) $cashRegister->id);

        $movement = Movement::create([
            'number'            => $number,
            'moved_at'          => $movedAt,
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Factura de pedido #' . $saleOrder->number,
            'status'            => 'A',
            'movement_type_id'  => $movementType->id,
            'document_type_id'  => $documentType->id,
            'branch_id'         => $branchId,
            'parent_movement_id'=> null,
            'shift_id'          => $shift->id,
            'shift_snapshot'    => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
        ]);

        $activeItems = $saleOrder->items()->get()->filter(
            fn ($i) => ((float) $i->quantity - (float) $i->returned_qty) > 0
        );
        $grandTotal = (float) $saleOrder->total;

        $salesMovement = SalesMovement::create([
            'branch_snapshot' => ['id' => $branch->id, 'legal_name' => $branch->legal_name],
            'series'          => $invoiceSeries,
            'billing_status'  => $billingStatus,
            'billing_number'  => $invoiceNumber,
            'year'            => Carbon::now()->year,
            'detail_type'     => 'DETALLADO',
            'consumption'     => 'N',
            'payment_type'    => 'CONTADO',
            'status'          => 'N',
            'sale_type'       => 'MINORISTA',
            'currency'        => $saleOrder->currency ?? 'PEN',
            'exchange_rate'   => $saleOrder->exchange_rate ?? 1,
            'subtotal'        => round($grandTotal / 1.18, 6),
            'tax'             => round($grandTotal - ($grandTotal / 1.18), 6),
            'total'           => $grandTotal,
            'movement_id'     => $movement->id,
            'branch_id'       => $branchId,
        ]);

        $defaultUnitId = Unit::query()->value('id');
        foreach ($activeItems as $item) {
            $activeQty = (float) $item->quantity - (float) $item->returned_qty;
            $product   = Product::with('baseUnit')->find($item->product_id);
            $unitId    = (int) ($product?->baseUnit?->id ?? $defaultUnitId);

            SalesMovementDetail::create([
                'detail_type'         => 'DETAILED',
                'sales_movement_id'   => $salesMovement->id,
                'code'                => $product?->code ?? '',
                'description'         => $product?->description ?? (string) data_get($item->product_snapshot, 'description', ''),
                'product_id'          => $item->product_id,
                'product_snapshot'    => $item->product_snapshot,
                'unit_id'             => $unitId,
                'tax_rate_id'         => null,
                'tax_rate_snapshot'   => null,
                'quantity'            => $activeQty,
                'amount'              => round($activeQty * (float) $item->unit_price, 6),
                'discount_percentage' => 0,
                'original_amount'     => round($activeQty * (float) $item->unit_price / 1.18, 6),
                'comment'             => null,
                'parent_detail_id'    => null,
                'complements'         => [],
                'status'              => 'A',
                'branch_id'           => $branchId,
            ]);
        }

        $cashMovementTypeId = $this->resolveCashMovementTypeId();
        $cashDocumentTypeId = $this->resolveCashIncomeDocumentTypeId($cashMovementTypeId);
        $paymentConcept     = $this->resolvePaymentConcept();

        $cashEntryMovement = Movement::create([
            'number'            => $this->generateCashMovementNumber($branchId, (int) $cashRegister->id),
            'moved_at'          => now(),
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Cobro de pedido #' . $saleOrder->number,
            'status'            => '1',
            'movement_type_id'  => $cashMovementTypeId,
            'document_type_id'  => $cashDocumentTypeId,
            'branch_id'         => $branchId,
            'parent_movement_id'=> $movement->id,
        ]);

        $saleOrder->loadMissing('payments');
        $registeredPayments   = $saleOrder->payments->filter(fn ($p) => !is_null($p->cash_movement_id));
        $unregisteredPayments = $saleOrder->payments->filter(fn ($p) => is_null($p->cash_movement_id));
        $unregisteredTotal    = $unregisteredPayments->sum(fn ($p) => (float) $p->amount);

        if ($registeredPayments->count() > 0) {
            $prevMvtIds = CashMovements::whereIn('id', $registeredPayments->pluck('cash_movement_id'))
                ->pluck('movement_id');
            Movement::whereIn('id', $prevMvtIds)->update(['parent_movement_id' => $movement->id]);
        }

        if ($unregisteredTotal > 0.001) {
            $cashMovement = CashMovements::create([
                'payment_concept_id' => $paymentConcept->id,
                'currency'           => 'PEN',
                'exchange_rate'      => 1.000,
                'total'              => $unregisteredTotal,
                'cash_register_id'   => $cashRegister->id,
                'cash_register'      => $cashRegister->number ?? 'Caja Principal',
                'shift_id'           => $shift->id,
                'shift_snapshot'     => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
                'movement_id'        => $cashEntryMovement->id,
                'branch_id'          => $branchId,
            ]);

            foreach ($unregisteredPayments as $payment) {
                DB::table('cash_movement_details')->insert([
                    'cash_movement_id'   => $cashMovement->id,
                    'type'               => 'PAGADO',
                    'paid_at'            => $payment->paid_at ?? now(),
                    'payment_method_id'  => $payment->payment_method_id,
                    'payment_method'     => $payment->payment_method ?? '',
                    'number'             => $cashEntryMovement->number,
                    'card_id'            => $payment->card_id,
                    'card'               => $payment->card ?? '',
                    'bank_id'            => null,
                    'bank'               => '',
                    'digital_wallet_id'  => $payment->digital_wallet_id,
                    'digital_wallet'     => $payment->digital_wallet ?? '',
                    'payment_gateway_id' => $payment->payment_gateway_id,
                    'payment_gateway'    => $payment->payment_gateway ?? '',
                    'amount'             => (float) $payment->amount,
                    'comment'            => 'Pago de pedido #' . $saleOrder->number,
                    'status'             => 'A',
                    'branch_id'          => $branchId,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        Kardex::query()->where('sale_order_id', $saleOrder->id)->delete();
        app(KardexSyncService::class)->syncMovement($movement);

        $saleOrder->movement_id = $movement->id;
        $saleOrder->status      = 'completed';
        $saleOrder->save();

        return $movement;
    }

    private function resolveDefaultDocumentTypeId(int $branchId): ?int
    {
        $documentTypes = DocumentType::query()
            ->where('movement_type_id', 2)
            ->orderBy('name')
            ->get(['id', 'name']);

        $configuredValue = DB::table('branch_parameters as bp')
            ->join('parameters as p', 'p.id', '=', 'bp.parameter_id')
            ->where('bp.branch_id', $branchId)
            ->whereNull('bp.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.description', 'ILIKE', '%tipo venta por defecto%')
            ->value('bp.value');

        if (is_numeric($configuredValue)) {
            $id = (int) $configuredValue;
            if ($documentTypes->contains(fn ($d) => (int) $d->id === $id)) {
                return $id;
            }
        }

        return $documentTypes->first()?->id ? (int) $documentTypes->first()->id : null;
    }

    private function getOpenCashRegisters(int $branchId): \Illuminate\Database\Eloquent\Collection
    {
        $openIds = CashShiftRelation::query()
            ->where('branch_id', $branchId)
            ->where('status', '1')
            ->with('cashMovementStart:id,cash_register_id')
            ->get()
            ->pluck('cashMovementStart.cash_register_id')
            ->filter()
            ->unique()
            ->values();

        return CashRegister::query()
            ->whereIn('id', $openIds)
            ->orderBy('number')
            ->get(['id', 'number', 'series']);
    }

    private function createInstantCashEntry(SaleOrder $saleOrder, SaleOrderPayment $payment, int $cashRegisterId, $user): ?int
    {
        $cashRegister = CashRegister::find($cashRegisterId);
        if (!$cashRegister) {
            return null;
        }

        $shift = Shift::where('branch_id', $saleOrder->branch_id)->first() ?? Shift::first();
        if (!$shift) {
            return null;
        }

        $cashMovementTypeId = $this->resolveCashMovementTypeId();
        $cashDocumentTypeId = $this->resolveCashIncomeDocumentTypeId($cashMovementTypeId);
        $paymentConcept     = $this->resolvePaymentConcept();

        $cashEntryMovement = Movement::create([
            'number'            => $this->generateCashMovementNumber($saleOrder->branch_id, $cashRegisterId),
            'moved_at'          => $payment->paid_at ?? now(),
            'user_id'           => $user?->id,
            'user_name'         => $user?->name ?? 'Sistema',
            'person_id'         => $saleOrder->person_id,
            'person_name'       => $saleOrder->person_name ?? 'Público General',
            'responsible_id'    => $user?->id,
            'responsible_name'  => $user?->name ?? 'Sistema',
            'comment'           => 'Adelanto/Pago de pedido #' . $saleOrder->number,
            'status'            => '1',
            'movement_type_id'  => $cashMovementTypeId,
            'document_type_id'  => $cashDocumentTypeId,
            'branch_id'         => $saleOrder->branch_id,
            'parent_movement_id'=> null,
        ]);

        $cashMovement = CashMovements::create([
            'payment_concept_id' => $paymentConcept->id,
            'currency'           => 'PEN',
            'exchange_rate'      => 1.000,
            'total'              => (float) $payment->amount,
            'cash_register_id'   => $cashRegisterId,
            'cash_register'      => $cashRegister->number ?? 'Caja Principal',
            'shift_id'           => $shift->id,
            'shift_snapshot'     => ['name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time],
            'movement_id'        => $cashEntryMovement->id,
            'branch_id'          => $saleOrder->branch_id,
        ]);

        DB::table('cash_movement_details')->insert([
            'cash_movement_id'   => $cashMovement->id,
            'type'               => 'PAGADO',
            'paid_at'            => $payment->paid_at ?? now(),
            'payment_method_id'  => $payment->payment_method_id,
            'payment_method'     => $payment->payment_method ?? '',
            'number'             => $cashEntryMovement->number,
            'card_id'            => $payment->card_id,
            'card'               => $payment->card ?? '',
            'bank_id'            => null,
            'bank'               => '',
            'digital_wallet_id'  => $payment->digital_wallet_id,
            'digital_wallet'     => $payment->digital_wallet ?? '',
            'payment_gateway_id' => $payment->payment_gateway_id,
            'payment_gateway'    => $payment->payment_gateway ?? '',
            'amount'             => (float) $payment->amount,
            'comment'            => 'Pago de pedido #' . $saleOrder->number,
            'status'             => 'A',
            'branch_id'          => $saleOrder->branch_id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $cashMovement->id;
    }
}
