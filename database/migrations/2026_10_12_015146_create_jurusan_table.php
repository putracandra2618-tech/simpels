<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        $jurusan = [
            ['TKJ', 'Teknik Komputer dan Jaringan'],
            ['RPL', 'Rekayasa Perangkat Lunak'],
            ['TEI', 'Teknik Elektronika Industri'],
            ['TPSB', 'Teknik Pemintalan Serat Buatan'],
            ['TB', 'Tata Busana'],
            ['TKR', 'Teknik Kendaraan Ringan'],
            ['TP', 'Teknik Pemesinan'],
        ];

        DB::table('jurusan')->insert(
            collect($jurusan)->map(fn (array $item) => [
                'kode' => $item[0],
                'nama' => $item[1],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan');
    }
};
