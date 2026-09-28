<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\ReturnRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_request_return(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $member = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create(['laptop_id' => $laptop->id]);
        $borrowing->students()->attach($member, ['created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($member)
            ->post(route('kembali.request', $borrowing));

        $response->assertRedirect(route('kembali.index'))
            ->assertSessionHas('success', 'Pengembalian dikirim ke Admin. Menunggu verifikasi.');

        $this->assertDatabaseHas('return_requests', [
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
        ]);
        $this->assertSame('menunggu', $borrowing->fresh()->status);

        Notification::assertSentTo($admin, ReturnRequestNotification::class);

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee($laptop->nama);
    }

    public function test_non_member_cannot_request_return(): void
    {
        $member = User::factory()->siswa()->create();
        $outsider = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create(['laptop_id' => $laptop->id]);
        $borrowing->students()->attach($member, ['created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($outsider)
            ->post(route('kembali.request', $borrowing))
            ->assertForbidden();
    }

    public function test_request_rejected_when_borrowing_not_active(): void
    {
        $member = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'menunggu',
        ]);
        $borrowing->students()->attach($member, ['created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($member)
            ->post(route('kembali.request', $borrowing))
            ->assertStatus(422);
    }

    public function test_admin_approval_marks_return_complete(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $member1 = User::factory()->siswa()->create();
        $member2 = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $borrowing = Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'menunggu',
        ]);
        $borrowing->students()->attach([$member1->id, $member2->id], ['created_at' => now(), 'updated_at' => now()]);

        $returnRequest = ReturnRequest::query()->create([
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.verifikasi', $returnRequest), [
                'keputusan' => 'disetujui',
                'condition' => 'Baik',
            ]);

        $response->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Pengembalian disetujui. Laptop kembali Tersedia.');

        $this->assertSame('disetujui', $returnRequest->fresh()->status);
        $this->assertNotNull($returnRequest->fresh()->responded_at);
        $this->assertSame('Baik', $returnRequest->fresh()->condition);

        $borrowing->refresh();
        $this->assertSame('dikembalikan', $borrowing->status);
        $this->assertNotNull($borrowing->returned_at);
        $this->assertSame('Baik', $borrowing->condition);

        $this->assertSame('tersedia', $borrowing->laptop->fresh()->status);

        $this->assertDatabaseMissing('return_requests', [
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.index'))
            ->assertSee('Tidak ada antrian verifikasi.');

        Notification::assertSentTo($member1, ReturnRequestNotification::class);
        Notification::assertSentTo($member2, ReturnRequestNotification::class);
    }

    public function test_admin_rejection_reactivates_borrowing(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $member = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $borrowing = Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'menunggu',
        ]);
        $borrowing->students()->attach($member, ['created_at' => now(), 'updated_at' => now()]);
        $laptop->update(['status' => 'dipinjam']);

        $returnRequest = ReturnRequest::query()->create([
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.verifikasi', $returnRequest), [
                'keputusan' => 'ditolak',
                'admin_notes' => 'Baterai rusak, harap periksa kembali.',
            ]);

        $response->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Pengembalian ditolak. Siswa dapat mengajukan kembali.');

        $this->assertSame('ditolak', $returnRequest->fresh()->status);
        $this->assertSame('Baterai rusak, harap periksa kembali.', $returnRequest->fresh()->admin_notes);
        $this->assertNotNull($returnRequest->fresh()->responded_at);

        $borrowing->refresh();
        $this->assertSame('aktif', $borrowing->status);
        $this->assertSame('dipinjam', $borrowing->laptop->fresh()->status);

        Notification::assertSentTo($member, ReturnRequestNotification::class);
    }
}
