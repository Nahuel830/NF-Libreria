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
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->foreignId('user_id')->constrained('users');
            $table->string('referencia_tipo', 30)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->text('motivo')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['producto_id', 'created_at']);
            $table->index(['referencia_tipo', 'referencia_id']);
        });

        DB::statement('ALTER TABLE movimientos_stock ADD CONSTRAINT movimientos_stock_cantidad_check CHECK (cantidad != 0)');
        DB::statement('ALTER TABLE movimientos_stock ADD CONSTRAINT movimientos_stock_consistencia_check CHECK (stock_nuevo = stock_anterior + cantidad)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
