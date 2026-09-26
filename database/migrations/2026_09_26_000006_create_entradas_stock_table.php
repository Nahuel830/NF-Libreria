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
        Schema::create('entradas_stock', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('fecha');
            $table->string('proveedor', 150)->nullable();
            $table->string('documento_referencia', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('total', 12, 2);
            $table->string('estado', 20)->default('REGISTRADA');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('anulada_por')->nullable()->constrained('users');
            $table->timestampTz('anulada_en')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestampsTz();

            $table->index('fecha');
            $table->index('estado');
        });

        Schema::create('detalle_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id')->constrained('entradas_stock')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->index('producto_id');
        });

        DB::statement('ALTER TABLE detalle_entradas ADD CONSTRAINT detalle_entradas_cantidad_check CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE detalle_entradas ADD CONSTRAINT detalle_entradas_costo_check CHECK (costo_unitario >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_entradas');
        Schema::dropIfExists('entradas_stock');
    }
};
