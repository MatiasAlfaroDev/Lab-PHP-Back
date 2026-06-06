<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones', function (Blueprint $table) {

            $table->id('excepcion_id');
            $table->unsignedBigInteger('profesional_id');
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('motivo')->nullable();
             $table->timestamps();
            $table->foreign('profesional_id')
                  ->references('user_id')
                  ->on('profesionales')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones');
    }
};