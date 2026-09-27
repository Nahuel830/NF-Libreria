<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entradas_stock', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
        });

        // Migrar textos existentes a proveedores vinculados.
        $textos = DB::table('entradas_stock')
            ->select('proveedor')
            ->whereNotNull('proveedor')
            ->where('proveedor', '!=', '')
            ->distinct()
            ->pluck('proveedor');

        foreach ($textos as $texto) {
            $proveedor = DB::table('proveedores')->where('nombre', $texto)->first();

            if (! $proveedor) {
                $id = DB::table('proveedores')->insertGetId([
                    'nombre' => $texto,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = $proveedor->id;
            }

            DB::table('entradas_stock')->where('proveedor', $texto)->update(['proveedor_id' => $id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entradas_stock', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
        });
    }
};
