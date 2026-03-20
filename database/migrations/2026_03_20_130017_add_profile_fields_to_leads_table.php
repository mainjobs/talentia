<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('ubicacion')->nullable()->after('telefono');
            $table->string('edad')->nullable()->after('ubicacion');
            $table->string('experiencia_anios')->nullable()->after('edad');
            $table->text('resumen_perfil')->nullable()->after('analisis_ia');
            $table->json('puntos_fuertes')->nullable()->after('resumen_perfil');
            $table->json('puntos_debiles')->nullable()->after('puntos_fuertes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            //
        });
    }
};
