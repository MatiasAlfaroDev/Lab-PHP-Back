<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_paquetes', function (Blueprint $table) {

            $table->id('compra_paquete_id');
            $table->date('fecha_compra');
            
            $table->foreignId('cliente_id')
                  ->references('user_id')
                  ->on('clientes')
                  ->onDelete('cascade');
            $table->foreignId('paquete_id')
                  ->references('paquete_id')
                  ->on('paquetes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_paquetes');
    }
};