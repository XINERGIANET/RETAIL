<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parameters')
            ->where('parameter_category_id', 2)
            ->where('description', 'Permitir venta sin stock')
            ->exists();

        if (!$exists) {
            DB::table('parameters')->insert([
                'description'           => 'Permitir venta sin stock',
                'value'                 => 'No',
                'status'                => 1,
                'parameter_category_id' => 2,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parameters')
            ->where('parameter_category_id', 2)
            ->where('description', 'Permitir venta sin stock')
            ->delete();
    }
};
