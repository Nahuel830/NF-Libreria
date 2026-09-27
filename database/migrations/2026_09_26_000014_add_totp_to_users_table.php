<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secreto')->nullable();
            $table->timestampTz('totp_confirmado_en')->nullable();
            $table->bigInteger('totp_ultimo_paso')->nullable();
            $table->text('codigos_recuperacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['totp_secreto', 'totp_confirmado_en', 'totp_ultimo_paso', 'codigos_recuperacion']);
        });
    }
};
