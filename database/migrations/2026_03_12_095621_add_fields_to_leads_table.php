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
                $table->boolean('in_clientify')->default(false)->after('cv_path');
                $table->timestamp('synced_at')->nullable()->after('in_clientify');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('in_clientify');
            $table->dropColumn('synced_at');
        });
    }
};
