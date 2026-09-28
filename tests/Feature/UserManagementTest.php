<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Kepala Lab']);
        $adminLain = User::factory()->admin()->create(['name' => 'Admin Tersembunyi', 'username' => 'admin2']);
        $siswa = User::factory()->siswa()->create(['name' => 'Budi Siswa', 'username' => '20240099']);

        $this->actingAs($admin)
            ->get(route('admin.user.index'))
            ->assertOk()
            ->assertSee('Budi Siswa')
            ->assertSee('20240099')
            ->assertSee('Tambah User')
            ->assertDontSee('Admin Tersembunyi');

        $this->assertDatabaseHas('users', [
            'name' => 'Budi Siswa',
            'username' => '20240099',
            'role' => $siswa->role,
        ]);
    }

    public function test_siswa_scope_lists_only_siswa(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'username' => 'bosbesar']);
        $admin = User::factory()->admin()->create(['name' => 'Si Admin']);
        $superadminLain = User::factory()->create(['role' => 'superadmin', 'name' => 'Si Super']);
        $siswa = User::factory()->siswa()->create(['name' => 'Si Murid', 'username' => '20240098']);

        $this->actingAs($superadmin)
            ->get(route('admin.user.index'))
            ->assertOk()
            ->assertSee('Si Murid')
            ->assertDontSee('Si Admin')
            ->assertDontSee('Si Super');

        $this->assertDatabaseHas('users', ['id' => $siswa->id, 'role' => 'siswa']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['id' => $superadminLain->id, 'role' => 'superadmin']);
    }

    public function test_admin_scope_lists_only_admins(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'username' => 'bosbesar']);
        $admin = User::factory()->admin()->create(['name' => 'Si Admin']);
        $superadminLain = User::factory()->create(['role' => 'superadmin', 'name' => 'Si Super']);
        $siswa = User::factory()->siswa()->create(['name' => 'Si Murid', 'username' => '20240097']);

        $this->actingAs($superadmin)
            ->get(route('admin.adminuser.index'))
            ->assertOk()
            ->assertSee('Si Admin')
            ->assertSee('Si Super')
            ->assertDontSee('Si Murid')
            ->assertDontSee('Kelas / Jurusan');
    }

    public function test_siswa_scope_rejects_creating_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Nyelip',
                'username' => 'nyelip2000',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'admin',
                'kelas' => 'X',
                'jurusan' => 'RPL',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'nyelip2000']);
    }

    public function test_admin_scope_rejects_creating_siswa_account(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'username' => 'bosbesar']);

        $this->actingAs($superadmin)
            ->post(route('admin.adminuser.store'), [
                'name' => 'Bukan Siswa',
                'username' => 'bukanmurid',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'siswa',
                'kelas' => 'X',
                'jurusan' => 'RPL',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'bukanmurid']);
    }

    public function test_admin_can_create_siswa_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Siti Aminah',
                'username' => '20240077',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'siswa',
                'kelas' => 'XII',
                'jurusan' => 'RPL',
            ])
            ->assertRedirect(route('admin.user.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Siti Aminah',
            'username' => '20240077',
            'role' => 'siswa',
            'kelas' => 'XII',
            'jurusan' => 'RPL',
        ]);
    }

    public function test_admin_can_edit_user_and_reset_password(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->siswa()->create(['username' => '20240066']);

        $this->actingAs($admin)
            ->put(route('admin.user.update', $siswa), [
                'name' => 'Siti Amalia',
                'username' => '20240066',
                'password' => 'barubaru123',
                'password_confirmation' => 'barubaru123',
                'role' => 'siswa',
                'kelas' => 'XI',
                'jurusan' => 'TEI',
            ])
            ->assertRedirect(route('admin.user.index'));

        $siswa->refresh();

        $this->assertSame('Siti Amalia', $siswa->name);
        $this->assertSame('XI', $siswa->kelas);
        $this->assertSame('TEI', $siswa->jurusan);
        $this->assertTrue(Hash::check('barubaru123', $siswa->password));
    }

    public function test_admin_can_delete_siswa_account(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = User::factory()->siswa()->create(['username' => '20240055']);

        $this->actingAs($admin)
            ->delete(route('admin.user.destroy', $siswa))
            ->assertRedirect(route('admin.user.index'));

        $this->assertDatabaseMissing('users', ['username' => '20240055']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.adminuser.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'bosadmin']);

        $this->actingAs($admin)
            ->put(route('admin.adminuser.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'password' => null,
                'role' => 'superadmin',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_regular_admin_cannot_create_superadmin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.adminuser.store'), [
                'name' => 'Intruder',
                'username' => 'evil2000',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'superadmin',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'evil2000']);
    }

    public function test_superadmin_can_create_superadmin_account(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'username' => 'bosbesar']);

        $this->actingAs($superadmin)
            ->post(route('admin.adminuser.store'), [
                'name' => 'Wakil Bos',
                'username' => 'boswakil',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'superadmin',
            ])
            ->assertRedirect(route('admin.adminuser.index'));

        $this->assertDatabaseHas('users', ['username' => 'boswakil', 'role' => 'superadmin']);
    }

    public function test_superadmin_can_access_admin_panel_and_login_via_admin_page(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'username' => 'putracandra']);

        $this->actingAs($superadmin)
            ->get(route('admin.adminuser.index'))
            ->assertOk();

        $this->assertTrue($superadmin->isAdmin());
        $this->assertTrue($superadmin->isSuperAdmin());

        $this->post(route('login.admin'), ['username' => 'putracandra', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($superadmin);
    }

    public function test_username_duplicate_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->siswa()->create(['username' => '20240044']);

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Duplikat',
                'username' => '20240044',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'siswa',
                'kelas' => 'X',
                'jurusan' => 'RPL',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_short_password_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Rendah',
                'username' => '20240033',
                'password' => '12345',
                'password_confirmation' => '12345',
                'role' => 'siswa',
                'kelas' => 'X',
                'jurusan' => 'RPL',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_siswa_account_requires_kelas_and_jurusan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.user.store'), [
                'name' => 'Tanpa Kelas',
                'username' => '20240022',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'siswa',
            ])
            ->assertSessionHasErrors(['kelas', 'jurusan']);

        $this->assertDatabaseMissing('users', ['username' => '20240022']);
    }
}
