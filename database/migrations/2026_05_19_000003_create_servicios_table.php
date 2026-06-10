<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {

            $table->id('servicio_id');
            $table->unsignedBigInteger('profesional_id');
            $table->string('nombre');
            $table->text('descripcion');
            $table->string('tipo');
            $table->decimal('precio', 10, 2);
            // duración en minutos
            $table->integer('duracion');
            // pausa entre sesiones en minutos
            $table->integer('pausa');
            $table->integer('min_cancelacion')->default(24);
            $table->enum('modalidad', [
                'virtual',
                'hibrido',
                'presencial'
            ]);
            $table->foreign('profesional_id')
                  ->references('user_id')
                  ->on('profesionales')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};