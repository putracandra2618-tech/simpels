<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_from_siswa_pages(): void
    {
        $this->get(route('pinjam.scan'))
            ->assertRedirect(route('login'));

        $this->get(route('kembali.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_siswa_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'dikembalikan',
        ]);

        $this->actingAs($admin)
            ->get(route('pinjam.scan'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('pinjam.konfirmasi', $laptop))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('kembali.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('pinjam.bukti', $borrowing))
            ->assertForbidden();
    }

    public function test_siswa_cannot_access_admin_pages(): void
    {
        $siswa = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create(['laptop_id' => $laptop->id]);
        $returnRequest = ReturnRequest::query()->create([
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $this->actingAs($siswa)
            ->get(route('admin.laptop.index'))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('admin.laptop.create'))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->get(route('admin.verifikasi.index'))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->post(route('admin.verifikasi', $returnRequest), ['keputusan' => 'disetujui'])
            ->assertForbidden();
    }

    public function test_admin_can_access_verifikasi_page_and_see_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();
        $borrowing = Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'menunggu',
        ]);
        $borrowing->students()->attach($member, ['created_at' => now(), 'updated_at' => now()]);
        $returnRequest = ReturnRequest::query()->create([
            'borrowing_id' => $borrowing->id,
            'status' => 'menunggu',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee($laptop->nama)
            ->assertSee('Setujui')
            ->assertSee('Tolak');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
