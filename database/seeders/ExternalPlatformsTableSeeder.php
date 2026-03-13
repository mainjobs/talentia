<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExternalPlatformsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('external_platforms')->insert([
            [
                'id' => 1,
                'name' => 'Clientify',
                'type' => 'crm',
                'url' => 'https://api.clientify.net',
                'description' => null,
                'active' => 1,
                'created_at' => '2026-01-22 13:36:31',
                'updated_at' => '2026-01-22 15:54:49',
            ],
        ]);
    }
}
