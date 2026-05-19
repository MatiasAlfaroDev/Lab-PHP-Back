<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_item_paquetes', function (Blueprint $table) {

            $table->id('compra_item_paquete_id');
            $table->integer('sesiones_restantes');
            $table->foreignId('compra_paquete_id')
                  ->references('compra_paquete_id')
                  ->on('compra_paquetes')
                  ->onDelete('cascade');
            $table->foreignId('item_paquete_id')
                  ->references('item_paquete_id')
                  ->on('item_paquetes')
                  ->onDelete('cascade');
            $table->unique([
                'compra_paquete_id',
                'item_paquete_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_item_paquetes');
    }
};