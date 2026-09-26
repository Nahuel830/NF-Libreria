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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('marca', 100)->nullable();
            $table->string('unidad', 20)->default('unidad');
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2);
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->boolean('controla_stock')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->index('nombre');
            $table->index('categoria_id');
            $table->index('activo');
        });

        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_precio_venta_check CHECK (precio_venta >= 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_precio_compra_check CHECK (precio_compra >= 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_stock_minimo_check CHECK (stock_minimo >= 0)');
        DB::statement('CREATE INDEX productos_nombre_lower_idx ON productos (lower(nombre))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
