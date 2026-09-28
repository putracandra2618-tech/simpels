<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_can_login_via_login_page(): void
    {
        $siswa = User::factory()->siswa()->create();

        $response = $this->post(route('login'), [
            'username' => $siswa->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($siswa);
    }

    public function test_admin_cannot_login_via_login_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_can_login_via_admin_login_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login.admin'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_siswa_cannot_login_via_admin_login_page(): void
    {
        $siswa = User::factory()->siswa()->create();

        $response = $this->from(route('login.admin'))->post(route('login.admin'), [
            'username' => $siswa->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login.admin'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $siswa = User::factory()->siswa()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'username' => $siswa->username,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_siswa_logout_redirects_to_login_page(): void
    {
        $siswa = User::factory()->siswa()->create();

        $response = $this->actingAs($siswa)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_logout_redirects_to_admin_login_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('logout'));

        $response->assertRedirect(route('login.admin'));
        $this->assertGuest();
    }
}
