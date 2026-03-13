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
        Schema::create('external_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la plataforma (ej: "Moodle Producción", "Salesforce")
            $table->string('type')->nullable(); // Tipo: moodle, crm, lms, etc.
            $table->string('url')->nullable(); // URL de la plataforma
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('ofertas', function (Blueprint $table) {
            $table->boolean('sync_with_crm')->default(false);
            $table->foreignId('external_platform_id')
                ->nullable()
                ->after('sync_with_crm')
                ->constrained('external_platforms')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            //
        });
    }
};
