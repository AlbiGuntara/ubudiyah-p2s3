<?php
namespace Database\Seeders;

use App\Models\Santri;
use Illuminate\Database\Seeder;

class SantriSeeder extends Seeder
{
    public function run(): void
    {
        $namaSantri = [
            'Ahmad Fauzi', 'Muhammad Rizki', 'Abdurrahman Wahid', 'Muhammad Sholeh',
            'Ahmad Syafi\'i', 'Muhammad Hasan', 'Abdullah Azzam', 'Muhammad Amin',
            'Ahmad Dahlan', 'Muhammad Yusuf', 'Ali Imron', 'Umar bin Khattab',
            'Usman bin Affan', 'Abu Bakar Ash-Shiddiq', 'Muhammad Al-Fatih',
            'Ahmad Hanafi', 'Muhammad Ikhsan', 'Abdurrahman Auf', 'Muhammad Zaki',
            'Ahmad Faruq', 'Muhammad Hisyam', 'Abdullah Ghozali', 'Muhammad Faiz',
            'Ahmad Muzakki', 'Muhammad Nabil', 'Ali Zainal', 'Muhammad Syukron',
            'Ahmad Badawi', 'Muhammad Rofi\'i', 'Muhammad Jamal',
            'Muhammad Siddiq', 'Ahmad Zaini', 'Muhammad Adib', 'Abdul Aziz',
        ];

        $santri = [];
        $nis = 1000;
        $index = 0;
        for ($asramaId = 1; $asramaId <= 15; $asramaId++) {
            for ($i = 0; $i < 2; $i++) {
                $santri[] = [
                    'nama' => $namaSantri[$index % count($namaSantri)],
                    'nis' => (string) ($nis++),
                    'iksass' => (function() {
                        $asal = ['Situbondo', 'Bondowoso', 'Jember', 'Banyuwangi', 'Probolinggo', 'Lumajang'];
                        return $asal[array_rand($asal)];
                    })(),
                    'asrama_id' => $asramaId,
                ];
                $index++;
            }
        }

        foreach ($santri as $s) {
            Santri::create($s);
        }
    }
}
