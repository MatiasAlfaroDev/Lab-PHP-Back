<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_paquetes', function (Blueprint $table) {

            $table->id('item_paquete_id');
            $table->integer('cantidad_sesiones');
            $table->foreignId('paquete_id')
                  ->references('paquete_id')
                  ->on('paquetes')
                  ->onDelete('cascade');
            $table->foreignId('servicio_id')
                  ->references('servicio_id')
                  ->on('servicios')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_paquetes');
    }
};