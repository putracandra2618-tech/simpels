<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $response->assertSessionHasErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
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
        $response->assertSessionHasErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
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
        $response->assertSessionHasErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
        $this->assertGuest();
    }

    public function test_login_failure_message_does_not_reveal_account_exists(): void
    {
        $existing = User::factory()->siswa()->create();

        $existenceResponse = $this->from(route('login'))->post(route('login'), [
            'username' => $existing->username,
            'password' => 'wrong-password',
        ]);

        $missingResponse = $this->from(route('login'))->post(route('login'), [
            'username' => 'tidak-pernah-ada',
            'password' => 'wrong-password',
        ]);

        $existenceResponse->assertSessionHasErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
        $missingResponse->assertSessionHasErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
    }

    public function test_role_mismatch_still_performs_a_password_hash_check(): void
    {
        $admin = User::factory()->admin()->create();

        Hash::spy();

        $this->from(route('login'))->post(route('login'), [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        Hash::shouldHaveReceived('check')
            ->once()
            ->withArgs(fn ($password, $hash) => $password === 'password' && $hash === $admin->getAuthPassword());
    }

    public function test_unknown_username_performs_a_dummy_hash_check(): void
    {
        Hash::spy();

        $this->from(route('login'))->post(route('login'), [
            'username' => 'tidak-ada-sama-sekali',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        Hash::shouldHaveReceived('check')->once();
    }

    public function test_login_is_rate_limited_per_ip(): void
    {
        $users = collect(range(0, 20))->map(
            fn (int $i) => User::factory()->siswa()->create(['username' => sprintf('2024%04d', $i)])
        );

        $users->take(20)->each(function (User $user) {
            $this->post(route('login'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ])->assertRedirect();
        });

        $this->post(route('login'), [
            'username' => $users->last()->username,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_login_is_rate_limited_per_username(): void
    {
        $siswa = User::factory()->siswa()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'username' => $siswa->username,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login'), [
            'username' => $siswa->username,
            'password' => 'wrong-password',
        ])->assertStatus(429);
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
