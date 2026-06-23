<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Servicio;
use App\Notifications\ReservaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosReserva extends Command
{
    protected $signature = 'reservas:recordatorios';

    protected $description = 'Envía recordatorios 24 horas antes de una reserva';

    public function handle()
    {
        $now = Carbon::now('America/Montevideo');

        Log::info('Iniciando recordatorios');

        // Ventana de 24h con tolerancia
        $desde = $now->copy()->addHours(23);
        $hasta = $now->copy()->addHours(25);

        $reservas = Reserva::whereIn('estado', ['confirmada', 'pagada'])
            ->whereNull('recordatorio_enviado_at')
            ->get();


        Log::info('Reservas encontradas', [
            'cantidad' => $reservas->count()
        ]);

        foreach ($reservas as $reserva) {

            if (!$reserva->fecha || !$reserva->hora) {
                continue;
            }

            $inicio = Carbon::parse(
                $reserva->fecha . ' ' . $reserva->hora,
                'America/Montevideo'
            );

            if ($inicio->between($desde, $hasta)) {

                $cliente = User::find($reserva->cliente_id);
                $servicio = Servicio::find($reserva->servicio_id);

                if (!$cliente || !$servicio) {
                    continue;
                }

                $profesional = User::find($servicio->profesional_id);

                Log::info('Enviando recordatorio', [
                    'reserva_id' => $reserva->reserva_id,
                    'cliente_id' => $reserva->cliente_id
                ]);

                try {
                    $cliente->notify(
                        new ReservaNotification(
                            'Recordatorio de Reserva',
                            "Te recordamos que tienes una reserva para el servicio: {$servicio->nombre}" .
                            ($profesional ? " con el profesional: {$profesional->name}" : ""),
                            $reserva->fecha,
                            $reserva->hora
                        )
                    );

                    DB::table('reservas')
                        ->where('reserva_id', $reserva->reserva_id)
                        ->update([
                            'recordatorio_enviado_at' => now()
                        ]);

                    $this->info("Enviado recordatorio reserva {$reserva->reserva_id}");
                } catch (\Throwable $e) {
                    Log::error('Fallo al enviar recordatorio', [
                        'reserva_id' => $reserva->reserva_id,
                        'cliente_id' => $reserva->cliente_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info('Proceso de recordatorios finalizado');
    }
}