<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use Carbon\Carbon;

class ActualizarReservasEnCurso extends Command
{
    protected $signature = 'reservas:en-curso';
    protected $description = 'Actualiza estados de reservas automáticamente';

    public function handle()
    {
        $now = Carbon::now();

        Reserva::with('servicio')
            ->whereIn('estado', ['confirmada', 'pagada', 'en_curso'])
            ->get()
            ->each(function ($reserva) use ($now) {

                $inicio = Carbon::parse(
                    $reserva->fecha . ' ' . substr($reserva->hora, 0, 5)
                );

                $fin = (clone $inicio)->addMinutes(
                    $reserva->servicio->duracion
                );

                if (
                    in_array($reserva->estado, ['confirmada', 'pagada']) &&
                    $inicio->lessThanOrEqualTo($now)
                ) {
                    $reserva->update([
                        'estado' => 'en_curso'
                    ]);
                }

                if (
                    $reserva->estado === 'en_curso' &&
                    $fin->lessThanOrEqualTo($now)
                ) {
                    $reserva->update([
                        'estado' => 'finalizada'
                    ]);
                }
            });

        $this->info('OK');
    }
}

//comando: php artisan schedule:work 