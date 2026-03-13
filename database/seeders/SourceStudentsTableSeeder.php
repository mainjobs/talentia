<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceStudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('source_students')->insert([
            [
                'id' => 1,
                'external_platform_id' => 1,
                'name' => 'Clientify',
                'description' => 'EIP',
                'short_description' => 'eip',
                'tag' => 'clientify',
                'token' => 'bf09db9e0628e7486f96b62d104d71f42cda6ad1',
                'active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 2,
                'external_platform_id' => 1,
                'name' => 'Clientify',
                'description' => 'Demo GrupoMainjobs',
                'short_description' => 'dgm',
                'tag' => 'clientify',
                'token' => '5f61381150c957f3db200a7e2027af63b88492ab',
                'active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
