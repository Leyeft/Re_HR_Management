<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data.
     * Run with: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
