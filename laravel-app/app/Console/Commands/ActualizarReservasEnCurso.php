<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Reserva;
use Carbon\Carbon;

class ActualizarReservasEnCurso extends Command
{
    protected $signature = 'reservas:en-curso';
    protected $description = 'Pasa reservas a en_curso cuando llega la hora';

    public function handle()
    {
        $now = Carbon::now();

        Reserva::whereIn('estado', ['confirmada', 'pagada'])
            ->get()
            ->each(function ($reserva) use ($now) {

                $fechaHora = Carbon::parse(
                    $reserva->fecha . ' ' . substr($reserva->hora, 0, 5)
                );

                if ($fechaHora->lessThanOrEqualTo($now)) {
                    $reserva->update(['estado' => 'en_curso']);
                }
            });

        $this->info('OK');
    }
}

//comando: php artisan schedule:work 