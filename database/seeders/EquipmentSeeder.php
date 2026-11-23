<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $catMap = Category::pluck('id', 'code');

        $equipments = [
            [
                'code' => 'EQP-KAM-001',
                'category_code' => 'KAT-KAM',
                'name' => 'Kamera Mirrorless Canon EOS M50 Mark II Kit 15-45mm',
                'description' => 'Kamera mirrorless 24.1MP 4K lengkap dengan lensa kit 15-45mm, baterai LP-E12, charger, dan SD Card 64GB.',
                'condition' => 'baik',
                'qty_total' => 4,
                'qty_available' => 4,
                'qty_damaged' => 0,
                'location' => 'Lemari Dry Cabinet A-01 (Studio MM)',
                'status' => 'tersedia',
                'notes' => 'Wajib dikembalikan beserta tutup lensa dan tas kamera.',
            ],
            [
                'code' => 'EQP-KAM-002',
                'category_code' => 'KAT-KAM',
                'name' => 'Camcorder Sony HXR-NX100 Full HD Professional',
                'description' => 'Camcorder profesional sensor 1.0-type Exmor R CMOS untuk peliputan kegiatan sekolah dan praktik broadcasting.',
                'condition' => 'baik',
                'qty_total' => 2,
                'qty_available' => 2,
                'qty_damaged' => 0,
                'location' => 'Lemari Dry Cabinet A-02 (Studio MM)',
                'status' => 'tersedia',
                'notes' => 'Termasuk baterai NP-F970 dan adaptor daya.',
            ],
            [
                'code' => 'EQP-KAM-003',
                'category_code' => 'KAT-KAM',
                'name' => 'Kamera DSLR Nikon D7500 Body + Lensa 18-140mm VR',
                'description' => 'Kamera DSLR untuk praktik fotografi jurnalistik dan dokumentasi acara.',
                'condition' => 'rusak_ringan',
                'qty_total' => 3,
                'qty_available' => 3,
                'qty_damaged' => 0,
                'location' => 'Lemari Dry Cabinet A-03 (Studio MM)',
                'status' => 'tersedia',
                'notes' => 'Karet eyecup sedikit longgar namun fungsi optik 100% normal.',
            ],
            [
                'code' => 'EQP-AUD-001',
                'category_code' => 'KAT-AUD',
                'name' => 'Microphone Wireless Rode Wireless GO II Dual Channel',
                'description' => 'Sistem mikrofon nirkabel 2 transmitter + 1 receiver dengan kabel TRS dan USB-C.',
                'condition' => 'baik',
                'qty_total' => 5,
                'qty_available' => 5,
                'qty_damaged' => 0,
                'location' => 'Laci Audio B-01 (Ruang Podcast)',
                'status' => 'tersedia',
                'notes' => 'Pastikan furry windshield tidak hilang.',
            ],
            [
                'code' => 'EQP-AUD-002',
                'category_code' => 'KAT-AUD',
                'name' => 'Portable Field Recorder Zoom H6 All Black',
                'description' => 'Perekam audio 6-track portabel dengan kapsul mikrofon X/Y untuk produksi film pendek.',
                'condition' => 'baik',
                'qty_total' => 3,
                'qty_available' => 3,
                'qty_damaged' => 0,
                'location' => 'Laci Audio B-02 (Ruang Podcast)',
                'status' => 'tersedia',
                'notes' => 'Dilengkapi koper pelindung keras.',
            ],
            [
                'code' => 'EQP-PRJ-001',
                'category_code' => 'KAT-PRJ',
                'name' => 'Proyektor Epson EB-X500 XGA 3600 Lumens',
                'description' => 'Proyektor presentasi kelas dan sidang UKK lengkap dengan kabel HDMI 5m dan remote.',
                'condition' => 'baik',
                'qty_total' => 6,
                'qty_available' => 6,
                'qty_damaged' => 0,
                'location' => 'Rak Inventaris C-01 (Lab RPL 1)',
                'status' => 'tersedia',
                'notes' => 'Tunggu kipas berhenti sebelum mencabut kabel daya.',
            ],
            [
                'code' => 'EQP-PRJ-002',
                'category_code' => 'KAT-PRJ',
                'name' => 'Pen Tablet Wacom Intuos Pro Medium PTH-660',
                'description' => 'Drawing tablet grafis dengan Wacom Pro Pen 2 untuk praktik desain UI/UX dan ilustrasi digital.',
                'condition' => 'baik',
                'qty_total' => 8,
                'qty_available' => 8,
                'qty_damaged' => 0,
                'location' => 'Rak Inventaris C-02 (Lab DKV)',
                'status' => 'tersedia',
                'notes' => 'Termasuk Pro Pen 2, dudukan pena, dan kabel USB-C.',
            ],
            [
                'code' => 'EQP-PRJ-003',
                'category_code' => 'KAT-PRJ',
                'name' => 'HDMI Video Capture Card Elgato Cam Link 4K',
                'description' => 'Konverter HDMI ke USB 3.0 untuk kebutuhan live streaming kegiatan sekolah.',
                'condition' => 'baik',
                'qty_total' => 3,
                'qty_available' => 3,
                'qty_damaged' => 0,
                'location' => 'Rak Inventaris C-03 (Lab RPL 1)',
                'status' => 'tersedia',
                'notes' => null,
            ],
            [
                'code' => 'EQP-JAR-001',
                'category_code' => 'KAT-JAR',
                'name' => 'Routerboard MikroTik hAP ac2 (RBD52G-5HacD2HnD)',
                'description' => 'Router dual-band gigabit 5 port untuk praktikum konfigurasi jaringan dan simulasi server.',
                'condition' => 'baik',
                'qty_total' => 10,
                'qty_available' => 10,
                'qty_damaged' => 0,
                'location' => 'Lemari Jaringan D-01 (Lab TJKT)',
                'status' => 'tersedia',
                'notes' => 'Reset konfigurasi setelah selesai praktikum.',
            ],
            [
                'code' => 'EQP-JAR-002',
                'category_code' => 'KAT-JAR',
                'name' => 'Toolkit Jaringan Lengkap (Crimping + LAN Tester + Punch Down)',
                'description' => 'Tas toolkit instalasi kabel UTP Cat6 lengkap dengan pengupas kabel dan LAN tester baterai 9V.',
                'condition' => 'baik',
                'qty_total' => 12,
                'qty_available' => 12,
                'qty_damaged' => 0,
                'location' => 'Lemari Jaringan D-02 (Lab TJKT)',
                'status' => 'tersedia',
                'notes' => null,
            ],
            [
                'code' => 'EQP-STD-001',
                'category_code' => 'KAT-STD',
                'name' => 'LED Studio Video Light Godox SL60W + Light Stand + Softbox',
                'description' => 'Lampu pencahayaan studio 60W 5600K lengkap dengan tripod lampu dan octabox 80cm.',
                'condition' => 'baik',
                'qty_total' => 4,
                'qty_available' => 4,
                'qty_damaged' => 0,
                'location' => 'Gudang Studio E-01 (Studio MM)',
                'status' => 'tersedia',
                'notes' => 'Pasang pelindung bohlam COB setelah dipakai.',
            ],
            [
                'code' => 'EQP-STD-002',
                'category_code' => 'KAT-STD',
                'name' => 'Tripod Video Fluid Head Takara Rover 66',
                'description' => 'Tripod aluminium beban maks 6kg dengan kepala fluid head untuk pergerakan panning halus.',
                'condition' => 'baik',
                'qty_total' => 6,
                'qty_available' => 6,
                'qty_damaged' => 0,
                'location' => 'Gudang Studio E-02 (Studio MM)',
                'status' => 'tersedia',
                'notes' => 'Quick-release plate wajib terpasang kembali.',
            ],
        ];

        foreach ($equipments as $item) {
            $categoryId = $catMap[$item['category_code']] ?? null;
            if (!$categoryId) {
                continue;
            }

            unset($item['category_code']);
            $item['category_id'] = $categoryId;

            Equipment::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
