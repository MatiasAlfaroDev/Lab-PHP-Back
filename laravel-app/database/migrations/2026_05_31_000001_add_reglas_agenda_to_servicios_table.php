<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            // Horas mínimas de anticipación para reservar
            $table->integer('min_aviso')->default(24)->after('min_cancelacion');
            // Días máximos a futuro para reservar
            $table->integer('max_anticipacion_dias')->default(60)->after('min_aviso');
            // Si true, la reserva se confirma automáticamente
            $table->boolean('aceptar_automaticamente')->default(true)->after('max_anticipacion_dias');
            // Si false, no se permiten reservas en feriados nacionales
            $table->boolean('permitir_feriados')->default(false)->after('aceptar_automaticamente');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn([
                'min_aviso',
                'max_anticipacion_dias',
                'aceptar_automaticamente',
                'permitir_feriados',
            ]);
        });
    }
};
