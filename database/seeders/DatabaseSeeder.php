<?php

namespace Database\Seeders;

use App\Models\Laptop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrator',
            'email' => null,
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::updateOrCreate(['username' => 'putracandra'], [
            'name' => 'Putra Candra',
            'email' => null,
            'password' => 'password',
            'role' => 'superadmin',
        ]);

        $siswaData = [
            '237891' => 'DANJI DWI PRASETYO',
            '2471021' => 'ABRIANI AFNANSYAH',
            '2471022' => 'AFIDATUNNISA',
            '2471023' => 'AIMATUN NISA',
            '2471024' => 'ALMIRA SYAFAWIBOWO',
            '2471025' => 'ANDREA FRANCY MAYANEZTA',
            '2471026' => 'APRILIA NURASY SYAHRANI',
            '2471027' => 'BARRA ANDRACESA KHARISMA',
            '2471028' => 'CINTA DAMAI CAHYANI',
            '2471029' => 'DESQUINEO JAYANAGARA',
            '2471030' => 'DEWI ARUM SARI',
            '2471031' => 'ERISKA VALENCIA PUTRI',
            '2471032' => 'FEBRIYAN ARBI UTAMA',
            '2471033' => 'HINDIANA SUKMA DEWI',
            '2471034' => 'IZZATI JAUHRUSYAFAR',
            '2471035' => 'KESYA AULIA AZ ZAHRA',
            '2471036' => 'LAUDYA CHINTIA BELLA',
            '2471037' => 'LUTFI AJI SUTANSYAH',
            '2471038' => 'MAULANA ZACKY PRATAMA',
            '2471039' => 'MUHAMAD BAGUS PRABOWO PUTRO',
            '2471040' => 'MUHAMMAD ADRIAN MAULANA',
            '2471041' => 'MUHAMMAD ILYAS',
            '2471042' => 'MUHAMMAD ISNAN RAMADHAN',
            '2471043' => 'MUHANNAD GUNTUR BINTANG PRATAMA',
            '2471044' => 'NADINDRA SIYANNA PUTRA',
            '2471045' => 'NAJWA AULIA ZAHRA',
            '2471046' => 'NATASYA TRIA YUNIAR',
            '2471047' => 'NAYAKA NOVA TIRTHA',
            '2471048' => 'NHEISYA LUNA ISMADILLA',
            '2471049' => 'NUR ALFA AL ZAHRA',
            '2471050' => 'NURITA',
            '2471051' => 'PUTRA WIDI CANDRA KANTA',
            '2471052' => 'RAFAEL LIONEL RINGGARD',
            '2471053' => 'RAKA ADITYA PUTRA',
            '2471054' => 'REHAN SALIFFATIN MURTAZA',
            '2471055' => 'REYHAN MARINO BUDIONO',
            '2471056' => 'SAFA ANNISA RAMADHANI',
            '2471057' => 'SHERLY AULINA',
            '2471058' => 'SILVIA NIRMALA ROHMADHONA',
            '2471059' => 'TANIA BUDI NISRINA',
            '2471060' => 'YOSAFAT NICO CHANDRA PRASODJO',
            '2471061' => 'AINA FAJAR RIZKIA',
        ];

        foreach ($siswaData as $nis => $nama) {
            User::updateOrCreate(['username' => $nis], [
                'name' => $nama,
                'email' => null,
                'password' => 'password',
                'role' => 'siswa',
                'kelas' => 'XII',
                'jurusan' => 'RPL',
            ]);
        }

        $laptops = [
            [
                'nama' => 'Lenovo ThinkPad T14',
                'merek' => 'Lenovo',
                'spesifikasi' => "Intel Core i5-1240P\nRAM 16 GB DDR4\nSSD 512 GB NVMe\nLayar 14\" FHD",
            ],
            [
                'nama' => 'Dell Latitude 3420',
                'merek' => 'Dell',
                'spesifikasi' => "Intel Core i5-1135G7\nRAM 8 GB DDR4\nSSD 256 GB\nLayar 14\" HD",
            ],
            [
                'nama' => 'Asus Vivobook 14',
                'merek' => 'Asus',
                'spesifikasi' => "AMD Ryzen 7 5700U\nRAM 16 GB DDR4\nSSD 512 GB\nLayar 14\" FHD IPS",
            ],
            [
                'nama' => 'HP ProBook 450 G8',
                'merek' => 'HP',
                'spesifikasi' => "Intel Core i5-1135G7\nRAM 8 GB DDR4\nSSD 512 GB\nLayar 15.6\" FHD",
            ],
            [
                'nama' => 'Acer Aspire 5',
                'merek' => 'Acer',
                'spesifikasi' => "Intel Core i3-1215U\nRAM 8 GB DDR4\nSSD 256 GB\nLayar 15.6\" FHD",
            ],
            [
                'nama' => 'MacBook Air 13',
                'merek' => 'Apple',
                'spesifikasi' => "Apple M1\nRAM 8 GB\nSSD 256 GB\nLayar 13.3\" Retina",
            ],
        ];

        foreach ($laptops as $data) {
            $token = Str::uuid()->toString();

            $laptop = Laptop::create([
                'nama' => $data['nama'],
                'merek' => $data['merek'],
                'spesifikasi' => $data['spesifikasi'],
                'qr_token' => $token,
                'status' => 'tersedia',
            ]);

            $this->generateQrImage($token);
        }
    }

    private function generateQrImage(string $token): void
    {
        $directory = storage_path('app/public/qrcodes');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        QrCode::format('png')
            ->size(512)
            ->margin(1)
            ->generate($token, $directory.DIRECTORY_SEPARATOR.$token.'.png');
    }
}
