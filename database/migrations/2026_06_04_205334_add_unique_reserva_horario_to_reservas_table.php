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
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique('uq_reserva_horario');
        });
        DB::statement("
            CREATE UNIQUE INDEX uq_reserva_horario
            ON reservas (servicio_id, fecha, hora)
            WHERE estado != 'cancelada'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique('uq_reserva_horario');
        });
    }
};

