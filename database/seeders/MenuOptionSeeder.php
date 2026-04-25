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
                        ['name' => 'Nueva empresa',    'action' => 'admin.companies.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar empresa',   'action' => 'admin.companies.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar empresa', 'action' => 'admin.companies.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Usuarios',
                    'action' => '/admin/herramientas/usuarios',
                    'icon'   => 'mdi-account-group',
                    'view'   => ['name' => 'Usuario', 'abbreviation' => 'users'],
                    'operations' => [],
                ],
                [
                    'name'   => 'Roles y permisos',
                    'action' => '/admin/herramientas/roles',
                    'icon'   => 'mdi-shield-account',
                    'view'   => ['name' => 'Rol', 'abbreviation' => 'roles'],
                    'operations' => [
                        ['name' => 'Editar Rol',   'action' => 'admin.roles.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Rol', 'action' => 'admin.roles.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Sucursales',
                    'action' => '/admin/herramientas/sucursales',
                    'icon'   => 'mdi-office-building',
                    'view'   => ['name' => 'Sucursal', 'abbreviation' => 'branches'],
                    'operations' => [
                        ['name' => 'Editar Sucursal',   'action' => 'admin.companies.branches.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Sucursal', 'action' => 'admin.companies.branches.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Modulos',
                    'action' => 'admin.modules.index',
                    'icon'   => 'mdi-view-module',
                    'view'   => ['name' => 'Modulo', 'abbreviation' => 'modules'],
                    'operations' => [
                        ['name' => 'Nuevo Modulo',    'action' => 'admin.modules.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Modulo',   'action' => 'admin.modules.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Modulo', 'action' => 'admin.modules.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Categorias de Parametros',
                    'action' => 'admin.parameters.categories.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Categoría parámetro', 'abbreviation' => 'parameters.categories'],
                    'operations' => [
                        ['name' => 'Eliminar Categoría parámetro', 'action' => 'admin.parameters.categories.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Parametros',
                    'action' => 'admin.parameters.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Parametros', 'abbreviation' => 'parameters'],
                    'operations' => [
                        ['name' => 'Eliminar Parametros', 'action' => 'admin.parameters.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Operaciones',
                    'action' => 'admin.operations.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Operacion', 'abbreviation' => 'operations'],
                    'operations' => [
                        ['name' => 'Nuevo Operacion',    'action' => 'admin.views.operations.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Operacion',   'action' => 'admin.views.operations.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Operacion', 'action' => 'admin.views.operations.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Vistas',
                    'action' => 'admin.views.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Vista', 'abbreviation' => 'views'],
                    'operations' => [
                        ['name' => 'Nueva Vista',    'action' => 'admin.views.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Vista',   'action' => 'admin.views.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Vista', 'action' => 'admin.views.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Tarjetas',
                    'action' => 'admin.cards.index',
                    'icon'   => 'mdi-credit-card',
                    'view'   => ['name' => 'Tarjeta', 'abbreviation' => 'cards'],
                    'operations' => [
                        ['name' => 'Nuevo Tarjeta',    'action' => 'admin.cards.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Tarjeta',   'action' => 'admin.cards.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tarjeta', 'action' => 'admin.cards.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Billeteras digitales',
                    'action' => 'admin.digital_wallets.index',
                    'icon'   => 'mdi-wallet',
                    'view'   => ['name' => 'Billetera digital', 'abbreviation' => 'digital_wallets'],
                    'operations' => [
                        ['name' => 'Nuevo Billetera digital',    'action' => 'admin.digital_wallets.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Billetera digital',   'action' => 'admin.digital_wallets.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Billetera digital', 'action' => 'admin.digital_wallets.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Tipo de movimientos',
                    'action' => 'admin.movement-types.index',
                    'icon'   => 'mdi-settings',
                    'view'   => ['name' => 'Tipo movimiento', 'abbreviation' => 'movement-types'],
                    'operations' => [
                        ['name' => 'Editar Tipo movimiento',   'action' => 'admin.movement-types.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tipo movimiento', 'action' => 'admin.movement-types.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
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
                        ['name' => 'Nueva Venta',    'action' => 'admin.sales.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Venta',   'action' => 'admin.sales.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Venta', 'action' => 'admin.sales.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Cotizaciones',
                    'action' => 'admin.quotes.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Cotización', 'abbreviation' => 'quotes'],
                    'operations' => [],
                ],
            ],
            'Compras' => [
                [
                    'name'   => 'Compras',
                    'action' => 'admin.purchases.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Compra', 'abbreviation' => 'purchases'],
                    'operations' => [
                        ['name' => 'Nueva Compra',    'action' => 'admin.purchases.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Compra',   'action' => 'admin.purchases.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Compra', 'action' => 'admin.purchases.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
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
                        ['name' => 'Editar Categoría',   'action' => 'admin.categories.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Categoría', 'action' => 'admin.categories.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Productos',
                    'action' => 'admin.products.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Producto', 'abbreviation' => 'products'],
                    'operations' => [
                        ['name' => 'Nuevo Producto',    'action' => 'admin.products.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Producto',   'action' => 'admin.products.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Producto', 'action' => 'admin.products.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Movimientos de almacen',
                    'action' => 'warehouse_movements.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Movimientos de almacén', 'abbreviation' => 'warehouse_movements'],
                    'operations' => [
                        ['name' => 'Entrada',                         'action' => 'warehouse_movements.input',   'icon' => 'ri-archive-line',    'color' => '#00A389', 'type' => 'T'],
                        ['name' => 'Salida',                          'action' => 'warehouse_movements.output',  'icon' => 'ri-archive-line',    'color' => '#EF4444', 'type' => 'T'],
                        ['name' => 'Editar Movimientos de almacén',   'action' => 'warehouse_movements.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Movimientos de almacén', 'action' => 'warehouse_movements.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                        ['name' => 'Detalles',                        'action' => 'warehouse_movements.show',    'icon' => 'ri-eye-line',        'color' => '#63B7EC', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Kardex',
                    'action' => 'kardex.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Kardex', 'abbreviation' => 'kardex'],
                    'operations' => [],
                ],
                [
                    'name'   => 'Tipos de producto',
                    'action' => 'product-types.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Tipo de producto', 'abbreviation' => 'product_types'],
                    'operations' => [
                        ['name' => 'Editar Tipo de producto',   'action' => 'product-types.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Tipo de producto', 'action' => 'product-types.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
            ],
            'Caja' => [
                [
                    'name'   => 'Caja',
                    'action' => 'boxes.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Caja', 'abbreviation' => 'boxes'],
                    'operations' => [
                        ['name' => 'Nueva Caja',    'action' => 'boxes.create',  'icon' => 'ri-add-line',        'color' => '#12f00e', 'type' => 'T'],
                        ['name' => 'Editar Caja',   'action' => 'boxes.edit',    'icon' => 'ri-pencil-line',     'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Caja', 'action' => 'boxes.destroy', 'icon' => 'ri-delete-bin-line', 'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Caja chica',
                    'action' => 'admin.petty-cash.base',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Caja chica', 'abbreviation' => 'petty-cash'],
                    'operations' => [
                        ['name' => 'Ingreso',            'action' => 'admin.petty-cash.store',   'icon' => 'ri-add-line',          'color' => '#00A389', 'type' => 'T'],
                        ['name' => 'Egreso',             'action' => 'admin.petty-cash.store',   'icon' => 'ri-subtract-line',     'color' => '#EF4444', 'type' => 'T'],
                        ['name' => 'Cerrar',             'action' => 'admin.petty-cash.close',   'icon' => 'ri-lock-2-line',       'color' => '#FACC15', 'type' => 'T'],
                        ['name' => 'Ver',                'action' => 'admin.petty-cash.show',    'icon' => 'ri-eye-line',          'color' => '#00BFFF', 'type' => 'R'],
                        ['name' => 'Editar Caja chica',  'action' => 'admin.petty-cash.edit',   'icon' => 'ri-pencil-line',       'color' => '#FBBF24', 'type' => 'R'],
                        ['name' => 'Eliminar Caja chica','action' => 'admin.petty-cash.destroy', 'icon' => 'ri-delete-bin-line',   'color' => '#EF4444', 'type' => 'R'],
                    ],
                ],
                [
                    'name'   => 'Turnos por caja',
                    'action' => 'admin.cash-shift-relations.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Turno por caja', 'abbreviation' => 'cash_shifts'],
                    'operations' => [],
                ],
            ],
            'Configuración' => [
                [
                    'name'   => 'Turnos',
                    'action' => 'shifts.index',
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
                    'action' => 'admin.system-config.index',
                    'icon'   => 'mdi-cash-register',
                    'view'   => ['name' => 'Configuración', 'abbreviation' => 'configuration'],
                    'operations' => [],
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
        $totalOpsUpdated    = 0;

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
                } elseif ($existing->view_id !== $viewId || $existing->action !== $opt['action']) {
                    DB::table('menu_option')
                        ->where('id', $existing->id)
                        ->update([
                            'view_id'    => $viewId,
                            'action'     => $opt['action'],
                            'updated_at' => now(),
                        ]);
                    $totalMenuUpdated++;
                }

                // 3. Sembrar operaciones de la vista (insertar o actualizar por nombre+view_id)
                foreach ($opt['operations'] as $op) {
                    $existingOp = DB::table('operations')
                        ->where('name', $op['name'])
                        ->where('view_id', $viewId)
                        ->first();

                    if (!$existingOp) {
                        // Verificar que no exista ya con la misma action (evitar duplicados)
                        $byAction = DB::table('operations')
                            ->where('action', $op['action'])
                            ->where('view_id', $viewId)
                            ->exists();

                        if (!$byAction) {
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
                    } elseif ($existingOp->action !== $op['action']) {
                        DB::table('operations')
                            ->where('id', $existingOp->id)
                            ->update([
                                'action'     => $op['action'],
                                'icon'       => $op['icon'],
                                'color'      => $op['color'],
                                'updated_at' => now(),
                            ]);
                        $totalOpsUpdated++;
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
        $this->command->info("   - Opciones de menú insertadas  : $totalMenuInserted");
        $this->command->info("   - Opciones de menú actualizadas: $totalMenuUpdated");
        $this->command->info("   - Operaciones insertadas       : $totalOpsInserted");
        $this->command->info("   - Operaciones actualizadas     : $totalOpsUpdated");
    }
}
