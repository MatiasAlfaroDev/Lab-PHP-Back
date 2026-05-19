<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {

            $table->id('reserva_id');

            $table->foreignId('cliente_id')
                  ->references('user_id')
                  ->on('clientes')
                  ->onDelete('cascade');

            $table->foreignId('servicio_id')
                  ->constrained('servicios', 'servicio_id')
                  ->cascadeOnDelete();

            $table->foreignId('compra_item_paquete_id')
                  ->nullable()
                  ->constrained('compra_item_paquetes', 'compra_item_paquete_id')
                  ->nullOnDelete();

            $table->date('fecha');

            $table->time('hora');

            $table->enum('estado', [
                'pendiente',
                'confirmada',
                'pagada',
                'en_curso',
                'cancelada',
                'finalizada',
                'no_asistida'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};