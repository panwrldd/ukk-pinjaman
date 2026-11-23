<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the GearHub application's database in relational dependency order.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
            EquipmentSeeder::class,
            TransactionDemoSeeder::class,
        ]);
    }
}
