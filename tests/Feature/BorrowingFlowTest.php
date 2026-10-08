<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_validasi_rejects_empty_token(): void
    {
        $scanner = User::factory()->siswa()->create();

        $this->actingAs($scanner)
            ->post(route('pinjam.validasi'), ['qr_token' => ''])
            ->assertRedirect(route('pinjam.scan'))
            ->assertSessionHas('error', 'QR Tidak Terbaca. Coba scan ulang.');
    }

    public function test_validasi_rejects_unknown_token(): void
    {
        $scanner = User::factory()->siswa()->create();

        $this->actingAs($scanner)
            ->post(route('pinjam.validasi'), ['qr_token' => 'unknown-token'])
            ->assertRedirect(route('pinjam.scan'))
            ->assertSessionHas('error', 'QR Tidak Terbaca. QR tidak terdaftar.');
    }

    public function test_validasi_rejects_laptop_that_is_being_borrowed(): void
    {
        $scanner = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'aktif',
        ]);
        $laptop->update(['status' => 'dipinjam']);

        $this->actingAs($scanner)
            ->post(route('pinjam.validasi'), ['qr_token' => $laptop->qr_token])
            ->assertRedirect(route('pinjam.scan'))
            ->assertSessionHas('error', "Laptop \"{$laptop->nama}\" sedang dipinjam.");
    }

    public function test_validasi_redirects_to_konfirmasi_for_available_laptop(): void
    {
        $scanner = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $this->actingAs($scanner)
            ->post(route('pinjam.validasi'), ['qr_token' => $laptop->qr_token])
            ->assertRedirect(route('pinjam.konfirmasi', $laptop));
    }

    public function test_konfirmasi_excludes_scanner_and_busy_students(): void
    {
        $scanner = User::factory()->siswa()->create();
        $busy = User::factory()->siswa()->create();
        $available = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowedLaptop = Laptop::factory()->create();

        $busyBorrowing = Borrowing::factory()->create([
            'laptop_id' => $borrowedLaptop->id,
            'created_by' => $busy->id,
        ]);
        $busyBorrowing->students()->attach($busy);

        $this->actingAs($scanner)
            ->get(route('pinjam.konfirmasi', $laptop))
            ->assertOk()
            ->assertSee($available->name)
            ->assertDontSee($busy->name)
            ->assertSee('Anda (scan)')
            ->assertViewHas('students', function ($students) use ($scanner, $busy) {
                return $students->count() === 1
                    && ! $students->contains('id', $scanner->id)
                    && ! $students->contains('id', $busy->id);
            });
    }

    public function test_store_records_borrowing_with_selected_members(): void
    {
        $scanner = User::factory()->siswa()->create();
        $other1 = User::factory()->siswa()->create();
        $other2 = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $response = $this->actingAs($scanner)
            ->post(route('pinjam.store', $laptop), [
                'member_ids' => [$other1->id, $other2->id],
            ]);

        $response->assertRedirect(route('pinjam.bukti', Borrowing::firstOrFail()));

        $this->assertDatabaseHas('borrowings', [
            'laptop_id' => $laptop->id,
            'status' => 'aktif',
        ]);
        $this->assertSame('dipinjam', $laptop->fresh()->status);

        $borrowing = Borrowing::firstOrFail();
        $this->assertSame(3, $borrowing->students()->count());
        $this->assertTrue($borrowing->students->contains('id', $scanner->id));
        $this->assertSame($scanner->id, $borrowing->created_by);
        $this->assertTrue($borrowing->leader->is($scanner));
        $this->assertTrue($borrowing->students->first()->is($scanner));
        $this->assertTrue($borrowing->students->take(2)->contains('id', $scanner->id));

        $this->get(route('pinjam.bukti', $borrowing))
            ->assertOk()
            ->assertSee('PML/PJM/'.str_pad((string) $borrowing->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_store_requires_at_least_one_member(): void
    {
        $scanner = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $this->actingAs($scanner)
            ->post(route('pinjam.store', $laptop), ['member_ids' => []])
            ->assertSessionHasErrors('member_ids');
    }

    public function test_store_blocks_more_than_five_members(): void
    {
        $scanner = User::factory()->siswa()->create();
        $others = User::factory()->siswa()->count(self::MAX_STUDENTS)->create();
        $laptop = Laptop::factory()->create();

        $this->actingAs($scanner)
            ->post(route('pinjam.store', $laptop), [
                'member_ids' => $others->pluck('id')->all(),
            ])
            ->assertSessionHas('error', 'Maksimal '.self::MAX_STUDENTS.' siswa dalam satu sesi peminjaman.');

        $this->assertDatabaseCount('borrowings', 0);
    }

    public function test_store_blocks_busy_student(): void
    {
        $scanner = User::factory()->siswa()->create();
        $busy = User::factory()->siswa()->create();
        $available = User::factory()->siswa()->create();
        $borrowedLaptop = Laptop::factory()->create();
        $laptop = Laptop::factory()->create();

        $busyBorrowing = Borrowing::factory()->create(['laptop_id' => $borrowedLaptop->id]);
        $busyBorrowing->students()->attach($busy);

        $this->actingAs($scanner)
            ->from(route('pinjam.konfirmasi', $laptop))
            ->post(route('pinjam.store', $laptop), [
                'member_ids' => [$busy->id, $available->id],
            ])
            ->assertRedirect(route('pinjam.konfirmasi', $laptop))
            ->assertSessionHas('error', "{$busy->name} sudah terdaftar di sesi peminjaman lain.");

        $this->assertDatabaseCount('borrowings', 1);
    }

    public function test_bukti_is_blocked_for_non_member_while_active(): void
    {
        $member = User::factory()->siswa()->create();
        $outsider = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $borrowing = Borrowing::factory()->create(['laptop_id' => $laptop->id]);
        $borrowing->students()->attach($member);

        $this->actingAs($outsider)
            ->get(route('pinjam.bukti', $borrowing))
            ->assertForbidden();
    }

    private const MAX_STUDENTS = 5;
}
