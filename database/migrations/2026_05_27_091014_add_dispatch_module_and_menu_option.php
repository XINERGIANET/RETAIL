<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Registro en views (necesario como FK en menu_option.view_id)
        $viewId = DB::table('views')->insertGetId([
            'name'         => 'Despacho',
            'abbreviation' => 'dispatch',
            'status'       => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'name'       => 'Despacho',
            'icon'       => 'ri-truck-line',
            'status'     => 1,
            'order_num'  => 9,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('menu_option')->insert([
            'name'         => 'Despacho',
            'action'       => 'admin.dispatch.index',
            'icon'         => 'ri-truck-line',
            'module_id'    => $moduleId,
            'view_id'      => $viewId,
            'quick_access' => false,
            'status'       => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    public function down(): void
    {
        $option = DB::table('menu_option')->where('action', 'admin.dispatch.index')->first();
        if ($option) {
            DB::table('menu_option')->where('id', $option->id)->delete();
            DB::table('modules')->where('id', $option->module_id)->delete();
            DB::table('views')->where('id', $option->view_id)->delete();
        }
    }
};
