<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['X', 'XI', 'XII'] as $nama) {
            Kelas::updateOrCreate(['nama' => $nama]);
        }

        $jurusan = [
            ['TKJ', 'Teknik Komputer dan Jaringan'],
            ['RPL', 'Rekayasa Perangkat Lunak'],
            ['TEI', 'Teknik Elektronika Industri'],
            ['TPSB', 'Teknik Pemintalan Serat Buatan'],
            ['TB', 'Tata Busana'],
            ['TKR', 'Teknik Kendaraan Ringan'],
            ['TP', 'Teknik Pemesinan'],
        ];

        foreach ($jurusan as [$kode, $nama]) {
            Jurusan::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
        }
    }
}
