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
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestampTz('fecha');
            $table->text('motivo');
            $table->decimal('total_devuelto', 12, 2);
            $table->string('metodo_reembolso', 20);
            $table->timestampsTz();
        });

        Schema::create('detalle_devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('detalle_venta_id')->constrained('detalle_ventas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->index('producto_id');
        });

        DB::statement('ALTER TABLE detalle_devoluciones ADD CONSTRAINT detalle_devoluciones_cantidad_check CHECK (cantidad > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_devoluciones');
        Schema::dropIfExists('devoluciones');
    }
};
