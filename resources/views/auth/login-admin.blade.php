<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Simpels</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/login-admin.css') }}">
</head>
<body class="login-admin">

    <div class="login-wrapper">
        <div class="login-card">

            <a href="{{ route('login.admin') }}" class="login-brand text-decoration-none">
                <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="login-brand-logo">
            </a>

            <p class="login-subtitle">Panel <strong>Administrator</strong> Simpels</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.admin') }}" method="POST" id="loginForm" class="needs-validation" novalidate>
                @csrf

                <div class="login-form-group">
                    <label for="username" class="login-form-label">Username</label>
                    <div class="login-input-group">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="username" name="username" class="login-input" placeholder="Masukkan username admin" value="{{ old('username') }}" required autofocus>
                    </div>
                </div>

                <div class="login-form-group">
                    <label for="password" class="login-form-label">Password</label>
                    <div class="login-input-group">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="login-input login-input-password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle-btn" id="toggle-password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="custom-control-label">
                        <input type="checkbox" class="custom-checkbox-input" id="rememberMe" name="remember">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-login" id="btn-submit">
                    <span>Masuk ke Panel Admin</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            <p class="login-footer-text mt-3">
                Belum punya akses?<a href="{{ route('login') }}"> Masuk sebagai Siswa</a>
            </p>

        </div>
    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/auth.js') }}"></script>
</body>
</html>