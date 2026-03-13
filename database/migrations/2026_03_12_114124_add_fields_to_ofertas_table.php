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
        Schema::create('source_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('short_description');
            $table->string('tag');
            $table->string('token')->nullable();
            $table->boolean('active');
            
            $table->timestamps();
        });

        
        Schema::table('ofertas', function (Blueprint $table) {
            $table->string('propietario_clientify')->nullable()->after('criterios_filtrado');    
            $table->json('etiqueta_clientify')->nullable()->after('propietario_clientify');
            $table->foreignId('source_student_id')->nullable();
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
