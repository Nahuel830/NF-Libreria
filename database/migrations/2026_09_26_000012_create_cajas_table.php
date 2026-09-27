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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->timestampTz('abierta_en');
            $table->decimal('monto_inicial', 12, 2);
            $table->timestampTz('cerrada_en')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users');
            $table->decimal('efectivo_esperado', 12, 2)->nullable();
            $table->decimal('efectivo_contado', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->jsonb('detalle_conteo')->nullable();
            $table->text('observaciones_cierre')->nullable();
            $table->string('estado', 20)->default('ABIERTA');
            $table->timestampsTz();
        });

        DB::statement('CREATE UNIQUE INDEX cajas_una_abierta_por_usuario ON cajas (user_id) WHERE estado = \'ABIERTA\'');
        DB::statement('ALTER TABLE cajas ADD CONSTRAINT cajas_monto_inicial_check CHECK (monto_inicial >= 0)');

        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas');
            $table->string('tipo', 20);
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto', 12, 2);
            $table->string('concepto', 200);
            $table->timestampTz('created_at')->useCurrent();

            $table->index('caja_id');
        });

        DB::statement('ALTER TABLE movimientos_caja ADD CONSTRAINT movimientos_caja_monto_check CHECK (monto > 0)');

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('caja_id')->nullable()->constrained('cajas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_id');
        });

        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
    }
};
