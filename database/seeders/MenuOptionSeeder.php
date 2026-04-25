<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuOptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando carga de Opciones de Menú...');

        /*
         * Cada entrada define:
         *   name       – nombre de la opción de menú
         *   action     – ruta o URL
         *   icon       – clase de ícono
         *   view       – ['name', 'abbreviation'] de la vista que representa la opción
         *   operations – operaciones que pertenecen a esa vista
         */
        $structure = [
            'Desarrollador' => [
                [
                    'name'   => 'Empresa',
                    'action' => 'admin.companies.index',
                    'icon'   => 'mdi-domain',
                    'view'   => ['name' => 'Empresa', 'abbreviation' => 'companies'],
                    'operations' => [
                        ['name' => 'Nueva empresa',    'action' => 'companies.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar empresa',   'action' => 'companies.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar empresa', 'action' => 'companies.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Usuarios',
                    'action' => '/admin/herramientas/usuarios',
                    'icon'   => 'mdi-account-group',
                    'view'   => ['name' => 'Usuario', 'abbreviation' => 'users'],
                    'operations' => [
                        ['name' => 'Nuevo Usuario',    'action' => 'users.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Usuario',   'action' => 'users.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Usuario', 'action' => 'users.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Roles y permisos',
                    'action' => '/admin/herramientas/roles',
                    'icon'   => 'mdi-shield-account',
                    'view'   => ['name' => 'Rol', 'abbreviation' => 'roles'],
                    'operations' => [
                        ['name' => 'Nuevo Rol',    'action' => 'roles.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Rol',   'action' => 'roles.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Rol', 'action' => 'roles.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Sucursales',
                    'action' => '/admin/herramientas/sucursales',
                    'icon'   => 'mdi-office-building',
                    'view'   => ['name' => 'Sucursal', 'abbreviation' => 'branches'],
                    'operations' => [
                        ['name' => 'Nuevo Sucursal',    'action' => 'branches.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Sucursal',   'action' => 'branches.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Sucursal', 'action' => 'branches.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Modulos',
                    'action' => 'admin.modules.index',
                    'icon'   => 'mdi-view-module',
                    'view'   => ['name' => 'Modulo', 'abbreviation' => 'modules'],
                    'operations' => [
                        ['name' => 'Nuevo Modulo',    'action' => 'modules.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Modulo',   'action' => 'modules.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Modulo', 'action' => 'modules.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Categorias de Parametros',
                    'action' => 'admin.parameters.categories.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Categoría parámetro', 'abbreviation' => 'parameters.categories'],
                    'operations' => [
                        ['name' => 'Nuevo Categoría parámetro',    'action' => 'parameters.categories.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Categoría parámetro',   'action' => 'parameters.categories.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Categoría parámetro', 'action' => 'parameters.categories.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Parametros',
                    'action' => 'admin.parameters.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Parametros', 'abbreviation' => 'parameters'],
                    'operations' => [
                        ['name' => 'Nuevo Parametros',    'action' => 'parameters.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Parametros',   'action' => 'parameters.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Parametros', 'action' => 'parameters.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Operaciones',
                    'action' => 'admin.operations.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Operacion', 'abbreviation' => 'operations'],
                    'operations' => [
                        ['name' => 'Nuevo Operacion',    'action' => 'operations.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Operacion',   'action' => 'operations.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Operacion', 'action' => 'operations.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Vistas',
                    'action' => 'admin.views.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Vista', 'abbreviation' => 'views'],
                    'operations' => [
                        ['name' => 'Nueva Vista',    'action' => 'views.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Vista',   'action' => 'views.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Vista', 'action' => 'views.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Tarjetas',
                    'action' => 'admin.cards.index',
                    'icon'   => 'mdi-credit-card',
                    'view'   => ['name' => 'Tarjeta', 'abbreviation' => 'cards'],
                    'operations' => [
                        ['name' => 'Nuevo Tarjeta',    'action' => 'cards.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Tarjeta',   'action' => 'cards.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tarjeta', 'action' => 'cards.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Billeteras digitales',
                    'action' => 'admin.digital_wallets.index',
                    'icon'   => 'mdi-wallet',
                    'view'   => ['name' => 'Billetera digital', 'abbreviation' => 'digital_wallets'],
                    'operations' => [
                        ['name' => 'Nuevo Billetera digital',    'action' => 'digitalwallets.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Billetera digital',   'action' => 'digitalwallets.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Billetera digital', 'action' => 'digitalwallets.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Tipo de movimientos',
                    'action' => 'admin.movement_types.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Tipo movimiento', 'abbreviation' => 'movement-types'],
                    'operations' => [
                        ['name' => 'Nuevo Tipo movimiento',    'action' => 'movement.types.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Tipo movimiento',   'action' => 'movement.types.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tipo movimiento', 'action' => 'movement.types.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Ventas' => [
                [
                    'name'   => 'Ventas',
                    'action' => 'admin.sales.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Venta', 'abbreviation' => 'sales'],
                    'operations' => [
                        ['name' => 'Nuevo Venta',    'action' => 'sales.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Venta',   'action' => 'sales.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Venta', 'action' => 'sales.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Cotizaciones',
                    'action' => 'admin.quotes.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Cotización', 'abbreviation' => 'quotes'],
                    'operations' => [
                        ['name' => 'Nueva Cotización',    'action' => 'quotes.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Cotización',   'action' => 'quotes.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Cotización', 'action' => 'quotes.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Compras' => [
                [
                    'name'   => 'Compras',
                    'action' => 'admin.purchases.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Compra', 'abbreviation' => 'purchases'],
                    'operations' => [
                        ['name' => 'Nuevo Compra',    'action' => 'purchases.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Compra',   'action' => 'purchases.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Compra', 'action' => 'purchases.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Almacen' => [
                [
                    'name'   => 'Categorias',
                    'action' => 'admin.categories.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Categoría', 'abbreviation' => 'categories'],
                    'operations' => [
                        ['name' => 'Nuevo Categoría',    'action' => 'categories.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Categoría',   'action' => 'categories.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Categoría', 'action' => 'categories.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Productos',
                    'action' => 'admin.products.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Producto', 'abbreviation' => 'products'],
                    'operations' => [
                        ['name' => 'Nuevo Producto',    'action' => 'products.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Producto',   'action' => 'products.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Producto', 'action' => 'products.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Movimientos de almacen',
                    'action' => 'admin.inventory.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Movimientos de almacén', 'abbreviation' => 'warehouse_movements'],
                    'operations' => [
                        ['name' => 'Entrada',                        'action' => 'warehouse_movements.input',   'icon' => 'ri-archive-line text-lg',  'color' => '#00A389', 'type' => 'T'],
                        ['name' => 'Salida',                         'action' => 'warehouse_movements.output',  'icon' => 'ri-archive-line text-lg"',  'color' => '#EF4444', 'type' => 'T'],
                        ['name' => 'Editar Movimientos de almacén',  'action' => 'warehouse_movements.edit',    'icon' => 'ri-pencil-line',             'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Movimientos de almacén','action' => 'warehousemovements.destroy',  'icon' => 'ri-delete-bin-line',         'color' => '#EF4444', 'type' => 'R'],
                        ['name' => 'Detalles',                       'action' => 'warehouse_movements.show',    'icon' => 'ri-eye-line',                'color' => '#63B7EC', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Kardex',
                    'action' => 'admin.inventory.kardex',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Kardex', 'abbreviation' => 'kardex'],
                    'operations' => [
                        ['name' => 'Nuevo Kardex',    'action' => 'kardex.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Kardex',   'action' => 'kardex.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Kardex', 'action' => 'kardex.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Tipos de producto',
                    'action' => 'admin.product_types.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Tipo de producto', 'abbreviation' => 'product_types'],
                    'operations' => [
                        ['name' => 'Nuevo Tipo de producto',    'action' => 'product.types.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Tipo de producto',   'action' => 'product.types.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tipo de producto', 'action' => 'product.types.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Caja' => [
                [
                    'name'   => 'Caja',
                    'action' => 'admin.cash.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Caja', 'abbreviation' => 'boxes'],
                    'operations' => [
                        ['name' => 'Nuevo Caja',    'action' => 'boxes.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Caja',   'action' => 'boxes.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Caja', 'action' => 'boxes.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Caja chica',
                    'action' => 'admin.petty_cash.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Caja chica', 'abbreviation' => 'petty-cash'],
                    'operations' => [
                        ['name' => 'Ingreso',           'action' => 'petty.cash.input',     'icon' => 'ri-add-line',          'color' => '#00A389', 'type' => 'T'],
                        ['name' => 'Egreso',            'action' => 'petty.cash.output',    'icon' => 'ri-subtract-line mr-1', 'color' => '#EF4444', 'type' => 'T'],
                        ['name' => 'Cerrar',            'action' => 'petty.cash.close',     'icon' => 'ri-lock-2-line',        'color' => '#FACC15', 'type' => 'T'],
                        ['name' => 'Ver',               'action' => 'admin.petty-cash.show','icon' => 'ri-eye-line',           'color' => '#00BFFF', 'type' => 'R'],
                        ['name' => 'Editar Caja chica', 'action' => 'petty.cash.edit',      'icon' => 'ri-pencil-line',        'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Caja chica','action' => 'petty.cash.destroy',  'icon' => 'ri-delete-bin-line',    'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Turnos por caja',
                    'action' => 'admin.cash_shifts.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Turno por caja', 'abbreviation' => 'cash_shifts'],
                    'operations' => [
                        ['name' => 'Nuevo Turno por caja',    'action' => 'cash.shifts.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Turno por caja',   'action' => 'cash.shifts.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Turno por caja', 'action' => 'cash.shifts.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Configuración' => [
                [
                    'name'   => 'Turnos',
                    'action' => 'admin.shifts.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Turno', 'abbreviation' => 'shifts'],
                    'operations' => [
                        ['name' => 'Nuevo Turno',    'action' => 'shifts.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Turno',   'action' => 'shifts.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Turno', 'action' => 'shifts.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Configuracion del sistema',
                    'action' => 'admin.configuration.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Configuración', 'abbreviation' => 'configuration'],
                    'operations' => [
                        ['name' => 'Editar Configuración', 'action' => 'configuration.edit', 'icon' => 'ri-pencil-line', 'color' => '#FBBF24', 'type' => 'R'],
                    ],
                ],
            ],
        ];

        // Cargar todas las vistas existentes (por abbreviation → id)
        $viewsMap = DB::table('views')
            ->whereNull('deleted_at')
            ->pluck('id', 'abbreviation')
            ->toArray();

        $totalMenuInserted  = 0;
        $totalMenuUpdated   = 0;
        $totalOpsInserted   = 0;

        foreach ($structure as $moduleName => $options) {

            $module = DB::table('modules')->where('name', $moduleName)->first();

            if (!$module) {
                $this->command->error("⚠️  El módulo '$moduleName' no existe. Saltando opciones...");
                continue;
            }

            foreach ($options as $opt) {

                // 1. Garantizar que la vista exista
                $viewAbbr = $opt['view']['abbreviation'];
                $viewName = $opt['view']['name'];

                if (!isset($viewsMap[$viewAbbr])) {
                    $newViewId = DB::table('views')->insertGetId([
                        'name'         => $viewName,
                        'abbreviation' => $viewAbbr,
                        'status'       => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    $viewsMap[$viewAbbr] = $newViewId;
                    $this->command->info("  🆕 Vista '$viewName' creada (ID: $newViewId)");
                }

                $viewId = $viewsMap[$viewAbbr];

                // 2. Insertar o actualizar la opción de menú
                $existing = DB::table('menu_option')
                    ->where('name', $opt['name'])
                    ->where('module_id', $module->id)
                    ->first();

                if (!$existing) {
                    DB::table('menu_option')->insert([
                        'name'         => $opt['name'],
                        'icon'         => $opt['icon'],
                        'action'       => $opt['action'],
                        'view_id'      => $viewId,
                        'module_id'    => $module->id,
                        'status'       => 1,
                        'quick_access' => false,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    $totalMenuInserted++;
                } elseif ($existing->view_id !== $viewId) {
                    DB::table('menu_option')
                        ->where('id', $existing->id)
                        ->update(['view_id' => $viewId, 'updated_at' => now()]);
                    $totalMenuUpdated++;
                }

                // 3. Sembrar operaciones de la vista (solo si no existen)
                foreach ($opt['operations'] as $op) {
                    $opExists = DB::table('operations')
                        ->where('action', $op['action'])
                        ->where('view_id', $viewId)
                        ->exists();

                    if (!$opExists) {
                        DB::table('operations')->insert([
                            'name'           => $op['name'],
                            'icon'           => $op['icon'],
                            'action'         => $op['action'],
                            'view_id'        => $viewId,
                            'color'          => $op['color'],
                            'type'           => $op['type'],
                            'status'         => 1,
                            'view_id_action' => null,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                        $totalOpsInserted++;
                    }
                }
            }
        }

        // Limpiar opciones de menú que ya no están en la estructura
        foreach ($structure as $moduleName => $options) {
            $module = DB::table('modules')->where('name', $moduleName)->first();
            if (!$module) {
                continue;
            }

            $allowedNames     = collect($options)->pluck('name')->all();
            $optionsToDelete  = DB::table('menu_option')
                ->where('module_id', $module->id)
                ->whereNotIn('name', $allowedNames)
                ->pluck('id');

            if ($optionsToDelete->isNotEmpty()) {
                DB::table('user_permission')
                    ->whereIn('menu_option_id', $optionsToDelete)
                    ->delete();

                DB::table('menu_option')
                    ->whereIn('id', $optionsToDelete)
                    ->delete();

                $this->command->warn("  🗑  Se eliminaron {$optionsToDelete->count()} opciones obsoletas del módulo '$moduleName'.");
            }
        }

        $this->command->info("✅ Seeder finalizado.");
        $this->command->info("   - Opciones de menú insertadas : $totalMenuInserted");
        $this->command->info("   - Opciones de menú actualizadas: $totalMenuUpdated (view_id corregido)");
        $this->command->info("   - Operaciones insertadas       : $totalOpsInserted");
    }
}
