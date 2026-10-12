<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::where('nama', 'XII')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240088', 'kelas_id' => $kelas->id]);

        $this->actingAs($admin)
            ->get(route('admin.kelas.index'))
            ->assertOk()
            ->assertSee('Kelas')
            ->assertSee('X')
            ->assertSee('XI')
            ->assertSee('XII')
            ->assertSee('1 siswa');
    }

    public function test_regular_user_cannot_access_kelas_page(): void
    {
        $siswa = User::factory()->siswa()->create();

        $this->actingAs($siswa)
            ->get(route('admin.kelas.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_kelas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.kelas.store'), ['nama' => 'XIII'])
            ->assertRedirect(route('admin.kelas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kelas', ['nama' => 'XIII']);
    }

    public function test_duplicate_kelas_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Kelas::create(['nama' => 'XIII']);

        $this->actingAs($admin)
            ->post(route('admin.kelas.store'), ['nama' => 'XIII'])
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Kelas::where('nama', 'XIII')->count());
    }

    public function test_admin_can_update_kelas(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::create(['nama' => 'XIII']);

        $this->actingAs($admin)
            ->put(route('admin.kelas.update', $kelas), ['nama' => 'XIV'])
            ->assertRedirect(route('admin.kelas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'nama' => 'XIV']);
    }

    public function test_kelas_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::where('nama', 'XII')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240087', 'kelas_id' => $kelas->id]);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $kelas))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_kelas_not_in_use_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $used = Kelas::where('nama', 'XII')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240086', 'kelas_id' => $used->id]);
        $empty = Kelas::create(['nama' => 'XIII']);

        $this->actingAs($admin)
            ->delete(route('admin.kelas.destroy', $empty))
            ->assertRedirect(route('admin.kelas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kelas', ['id' => $empty->id]);
        $this->assertDatabaseHas('kelas', ['id' => $used->id]);
    }
}
