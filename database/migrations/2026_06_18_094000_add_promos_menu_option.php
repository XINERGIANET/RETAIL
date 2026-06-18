<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Asegurarnos que exista la opción de menú
        $moduleId = DB::table('modules')->where('name', 'Almacen')->value('id');
        $viewId = DB::table('views')->where('name', 'Promo')->value('id');
        
        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'Almacen',
                'description' => 'Modulo de Almacen',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$viewId) {
            $viewId = DB::table('views')->insertGetId([
                'name' => 'Promo',
                'abbreviation' => 'promos',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $menuOption = DB::table('menu_option')->where('name', 'Promos')->first();
        $menuOptionId = null;

        if (!$menuOption) {
            $menuOptionId = DB::table('menu_option')->insertGetId([
                'name' => 'Promos',
                'action' => 'admin.promos.index',
                'icon' => 'mdi-cash-register',
                'view_id' => $viewId,
                'module_id' => $moduleId,
                'status' => 1,
                'quick_access' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $menuOptionId = $menuOption->id;
        }

        // 2. Asignar permisos a la opción de menú para el rol administrador (profile_id = 1) en todas las sucursales
        if ($menuOptionId) {
            $branches = DB::table('branches')->pluck('id');
            
            foreach ($branches as $branchId) {
                $exists = DB::table('user_permission')
                    ->where('profile_id', 1)
                    ->where('menu_option_id', $menuOptionId)
                    ->where('branch_id', $branchId)
                    ->exists();
                    
                if (!$exists) {
                    DB::table('user_permission')->insert([
                        'name' => 'Promos',
                        'profile_id' => 1,
                        'menu_option_id' => $menuOptionId,
                        'branch_id' => $branchId,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menuOptionId = DB::table('menu_option')->where('name', 'Promos')->value('id');
        
        if ($menuOptionId) {
            DB::table('user_permission')->where('menu_option_id', $menuOptionId)->delete();
            DB::table('menu_option')->where('id', $menuOptionId)->delete();
        }
    }
};
