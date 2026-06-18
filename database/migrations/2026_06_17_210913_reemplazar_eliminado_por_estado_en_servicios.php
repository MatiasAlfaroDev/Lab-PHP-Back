<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('servicios', function (Blueprint $table) {
        $table->string('estado')->default('activo');
    });

    DB::table('servicios')
        ->whereRaw('eliminado IS TRUE')
        ->update([
            'estado' => 'eliminado'
        ]);

    Schema::table('servicios', function (Blueprint $table) {
        $table->dropColumn('eliminado');
    });
}

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('eliminado')
                ->default(false);
        });

        DB::table('servicios')
    ->where('estado', 'eliminado')
    ->update([
        'eliminado' => true
    ]);

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};