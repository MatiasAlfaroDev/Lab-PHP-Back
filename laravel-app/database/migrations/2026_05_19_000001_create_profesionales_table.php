<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionales', function (Blueprint $table) {

            $table->foreignId('user_id')
                  ->primary()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->text('descripcion');
            $table->string('ubicacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};