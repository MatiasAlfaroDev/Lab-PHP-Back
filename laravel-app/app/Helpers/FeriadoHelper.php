<?php

namespace App\Helpers;

use Carbon\Carbon;

class FeriadoHelper
{
    // Feriados nacionales argentinos fijos por año
    private static array $feriadosFijos = [
        '01-01', // Año Nuevo
        '03-24', // Día Nacional de la Memoria
        '04-02', // Día del Veterano y Caídos en Malvinas
        '05-01', // Día del Trabajador
        '05-25', // Día de la Patria (Revolución de Mayo)
        '06-20', // Paso a la Inmortalidad del Gral. Belgrano
        '07-09', // Día de la Independencia
        '12-08', // Inmaculada Concepción de María
        '12-25', // Navidad
    ];

    // Feriados móviles por año (2026 y 2027 cubiertos)
    private static array $feriadosMoviles = [
        2026 => [
            '02-16', // Carnaval
            '02-17', // Carnaval
            '04-03', // Viernes Santo
            '08-17', // Paso a la Inmortalidad del Gral. San Martín
            '10-12', // Día del Respeto a la Diversidad Cultural
            '11-20', // Día de la Soberanía Nacional
        ],
        2027 => [
            '02-08', // Carnaval
            '02-09', // Carnaval
            '03-26', // Viernes Santo
            '08-16', // Paso a la Inmortalidad del Gral. San Martín
            '10-11', // Día del Respeto a la Diversidad Cultural
            '11-22', // Día de la Soberanía Nacional
        ],
    ];

    public static function esFeriado(string $fecha): bool
    {
        $carbon = Carbon::parse($fecha);
        $year   = $carbon->year;
        $mmdd   = $carbon->format('m-d');

        if (in_array($mmdd, self::$feriadosFijos)) {
            return true;
        }

        $moviles = self::$feriadosMoviles[$year] ?? [];
        return in_array($mmdd, $moviles);
    }
}
