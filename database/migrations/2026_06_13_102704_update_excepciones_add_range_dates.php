<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excepciones', function (Blueprint $table) {
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
        });

        DB::statement("
            UPDATE excepciones
            SET fecha_desde = fecha,
                fecha_hasta = fecha
        ");

        Schema::table('excepciones', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('excepciones', function (Blueprint $table) {
            $table->date('fecha')->nullable();
        });

        DB::statement("
            UPDATE excepciones
            SET fecha = fecha_desde
        ");

        Schema::table('excepciones', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_desde',
                'fecha_hasta'
            ]);
        });
    }
};