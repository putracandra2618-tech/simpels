<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showAdminLogin(): View
    {
        return view('auth.login-admin');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $role = $request->route('role', 'siswa');
        $role = in_array($role, ['siswa', 'admin'], true) ? $role : 'siswa';

        $user = User::where('username', $credentials['username'])->first();

        if ($user !== null && ! $this->matchesRole($user->role, $role)) {
            $label = $role === 'admin' ? 'Admin' : 'Siswa';

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => "Akun ini bukan akun {$label}. Gunakan halaman login yang sesuai."]);
        }

        if ($user === null || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Login Gagal. Periksa kembali username atau password Anda.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $isAdmin = optional($request->user())->isAdmin();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($isAdmin ? 'login.admin' : 'login');
    }

    private function matchesRole(string $userRole, string $loginRole): bool
    {
        if ($loginRole === 'siswa') {
            return $userRole === 'siswa';
        }

        return in_array($userRole, ['admin', 'superadmin'], true);
    }
}
