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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->timestampTz('fecha');
            $table->foreignId('user_id')->constrained('users');
            $table->string('cliente_nombre', 150)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('metodo_pago', 20);
            $table->decimal('monto_recibido', 12, 2)->nullable();
            $table->decimal('cambio', 12, 2)->nullable();
            $table->string('estado', 20)->default('COMPLETADA');
            $table->text('observaciones')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users');
            $table->timestampTz('anulada_en')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestampsTz();

            $table->index('fecha');
            $table->index(['user_id', 'fecha']);
            $table->index('estado');
        });

        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->string('codigo_producto', 30);
            $table->string('nombre_producto', 150);
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->index('producto_id');
        });

        DB::statement('ALTER TABLE ventas ADD CONSTRAINT ventas_total_check CHECK (total >= 0)');
        DB::statement('ALTER TABLE ventas ADD CONSTRAINT ventas_descuento_check CHECK (descuento >= 0)');
        DB::statement('ALTER TABLE ventas ADD CONSTRAINT ventas_descuento_subtotal_check CHECK (descuento <= subtotal)');
        DB::statement('ALTER TABLE detalle_ventas ADD CONSTRAINT detalle_ventas_cantidad_check CHECK (cantidad > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
    }
};
