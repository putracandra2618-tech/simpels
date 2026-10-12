<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_jurusan(): void
    {
        $admin = User::factory()->admin()->create();
        $tkj = Jurusan::where('kode', 'TKJ')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240088', 'jurusan_id' => $tkj->id]);

        $this->actingAs($admin)
            ->get(route('admin.jurusan.index'))
            ->assertOk()
            ->assertSee('Jurusan')
            ->assertSee('TKJ')
            ->assertSee('Teknik Komputer dan Jaringan')
            ->assertSee('1 siswa');
    }

    public function test_regular_user_cannot_access_jurusan_page(): void
    {
        $siswa = User::factory()->siswa()->create();

        $this->actingAs($siswa)
            ->get(route('admin.jurusan.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_jurusan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.jurusan.store'), [
                'kode' => 'MM',
                'nama' => 'Multimedia',
            ])
            ->assertRedirect(route('admin.jurusan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jurusan', ['kode' => 'MM', 'nama' => 'Multimedia']);
    }

    public function test_duplicate_jurusan_kode_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Jurusan::create(['kode' => 'MM', 'nama' => 'Multimedia']);

        $this->actingAs($admin)
            ->post(route('admin.jurusan.store'), [
                'kode' => 'MM',
                'nama' => 'Animasi',
            ])
            ->assertSessionHasErrors('kode');

        $this->assertSame(1, Jurusan::where('kode', 'MM')->count());
    }

    public function test_admin_can_update_jurusan(): void
    {
        $admin = User::factory()->admin()->create();
        $jurusan = Jurusan::create(['kode' => 'MM', 'nama' => 'Multimedia']);

        $this->actingAs($admin)
            ->put(route('admin.jurusan.update', $jurusan), [
                'kode' => 'MUL',
                'nama' => 'Multimedia',
            ])
            ->assertRedirect(route('admin.jurusan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jurusan', ['id' => $jurusan->id, 'kode' => 'MUL']);
    }

    public function test_jurusan_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $jurusan = Jurusan::where('kode', 'TKJ')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240085', 'jurusan_id' => $jurusan->id]);

        $this->actingAs($admin)
            ->delete(route('admin.jurusan.destroy', $jurusan))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('jurusan', ['id' => $jurusan->id]);
    }

    public function test_jurusan_not_in_use_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $used = Jurusan::where('kode', 'TKJ')->firstOrFail();
        User::factory()->siswa()->create(['username' => '20240084', 'jurusan_id' => $used->id]);
        $empty = Jurusan::create(['kode' => 'MM', 'nama' => 'Multimedia']);

        $this->actingAs($admin)
            ->delete(route('admin.jurusan.destroy', $empty))
            ->assertRedirect(route('admin.jurusan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('jurusan', ['id' => $empty->id]);
        $this->assertDatabaseHas('jurusan', ['id' => $used->id]);
    }
}
