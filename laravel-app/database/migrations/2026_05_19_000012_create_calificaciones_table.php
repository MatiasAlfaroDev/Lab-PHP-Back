<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {

            $table->id('calificacion_id');
            $table->integer('puntuacion');
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->foreignId('reserva_id')
                  ->references('reserva_id')
                  ->on('reservas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};