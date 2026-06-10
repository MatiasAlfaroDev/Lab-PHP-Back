<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {

            $table->id('pago_id');
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->enum('estado', [
                'pendiente',
                'aprobado',
                'rechazado',
                'cancelado',
                'fallido'
            ]);
            $table->enum('metodo', [
                'paypal',
                'presencial'
            ]);
            $table->foreignId('reserva_id')
                ->nullable()
                ->constrained('reservas', 'reserva_id')
                ->cascadeOnDelete();

            $table->foreignId('compra_paquete_id')
                ->nullable()
                ->constrained('compra_paquetes', 'compra_paquete_id')
                ->cascadeOnDelete();
                    
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};