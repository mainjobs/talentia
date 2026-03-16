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
            $table->string('nombre')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('telefono')->nullable()->change();
            // Si datos_extraidos y analisis_ia tampoco son nullable, añádelos:
            $table->text('datos_extraidos')->nullable()->change();
            $table->text('analisis_ia')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
