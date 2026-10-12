<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('role')->constrained('kelas')->nullOnDelete();
            $table->foreignId('jurusan_id')->nullable()->after('kelas_id')->constrained('jurusan')->nullOnDelete();
        });

        $this->backfillReference('kelas', 'kelas_id', 'kelas', 'nama');
        $this->backfillReference('jurusan', 'jurusan_id', 'jurusan', 'kode');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kelas', 'jurusan']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kelas')->nullable()->after('role');
            $table->string('jurusan')->nullable()->after('kelas');
        });

        $this->restoreReference('kelas_id', 'kelas', 'kelas', 'nama');
        $this->restoreReference('jurusan_id', 'jurusan', 'jurusan', 'kode');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropConstrainedForeignId('jurusan_id');
        });
    }

    private function backfillReference(string $from, string $to, string $referenceTable, string $referenceColumn): void
    {
        DB::table('users')
            ->select('id', $from)
            ->whereNotNull($from)
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($from, $to, $referenceTable, $referenceColumn): void {
                $reference = DB::table($referenceTable)->where($referenceColumn, $user->{$from})->first();

                if ($reference !== null) {
                    DB::table('users')->where('id', $user->id)->update([$to => $reference->id]);
                }
            });
    }

    private function restoreReference(string $from, string $to, string $referenceTable, string $referenceColumn): void
    {
        DB::table('users')
            ->select('id', $from)
            ->whereNotNull($from)
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($from, $to, $referenceTable, $referenceColumn): void {
                $reference = DB::table($referenceTable)->where('id', $user->{$from})->first();

                if ($reference !== null) {
                    DB::table('users')->where('id', $user->id)->update([$to => $reference->{$referenceColumn}]);
                }
            });
    }
};
