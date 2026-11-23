<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'KAT-KAM',
                'name' => 'Kamera & Videografi',
                'slug' => 'kamera-videografi',
                'description' => 'Kamera mirrorless, DSLR, camcorder, lensa, dan aksesoris pengambilan gambar.',
                'is_active' => true,
            ],
            [
                'code' => 'KAT-AUD',
                'name' => 'Audio & Podcast',
                'slug' => 'audio-podcast',
                'description' => 'Mikrofon wireless, audio recorder, soundcard, dan headphone monitoring studio.',
                'is_active' => true,
            ],
            [
                'code' => 'KAT-PRJ',
                'name' => 'Proyektor & Display',
                'slug' => 'proyektor-display',
                'description' => 'LCD proyektor ruang praktik, layar portabel, capture card, dan pen tablet.',
                'is_active' => true,
            ],
            [
                'code' => 'KAT-JAR',
                'name' => 'Laboratorium Jaringan & IoT',
                'slug' => 'laboratorium-jaringan-iot',
                'description' => 'Router Mikrotik, switch manageable, crimping kit, LAN tester, dan perangkat praktik jaringan.',
                'is_active' => true,
            ],
            [
                'code' => 'KAT-STD',
                'name' => 'Tata Cahaya & Penyangga Studio',
                'slug' => 'tata-cahaya-penyangga-studio',
                'description' => 'LED video light, softbox, tripod fluida, gimbal stabilizer, dan perlengkapan studio.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
