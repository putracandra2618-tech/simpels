<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Simpels</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/login-simple.css') }}">
</head>
<body class="login-simple">

    <div class="simple-login">

        <div class="simple-login__form">
            <div class="simple-login__form-inner">

                <a href="{{ route('login') }}" class="simple-login__brand">
                    <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="simple-login__logo-sm">
                    <span>Simpels</span>
                </a>

                <h1 class="simple-login__title">Login ke Simpels</h1>
                <p class="simple-login__subtitle">sistem peminjaman laptop sekolah</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" id="loginForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="simple-login__field">
                        <label for="username" class="simple-login__label">Username (NIS)</label>
                        <div class="simple-login__input-wrap">
                            <i class="bi bi-person simple-login__input-icon"></i>
                            <input type="text" id="username" name="username" class="simple-login__input" placeholder="Masukkan NIS / username" value="{{ old('username') }}" required autofocus>
                        </div>
                    </div>

                    <div class="simple-login__field">
                        <label for="password" class="simple-login__label">Password</label>
                        <div class="simple-login__input-wrap">
                            <i class="bi bi-shield-lock simple-login__input-icon"></i>
                            <input type="password" id="password" name="password" class="simple-login__input simple-login__input-password" placeholder="••••••••" required>
                            <button type="button" class="simple-login__toggle" id="toggle-password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="simple-login__options">
                        <label class="custom-control-label">
                            <input type="checkbox" class="custom-checkbox-input" id="rememberMe" name="remember">
                            <span>Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-login simple-login__submit" id="btn-submit">
                        <span>Login </span>
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </form>

            </div>
        </div>

        <div class="simple-login__info">
            <div class="simple-login__overlay"></div>
            <div class="simple-login__info-content">
                <div class="simple-login__logo-badge">
                    <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels">
                </div>
                <p class="simple-login__big">SIMPELS</p>
                <p class="simple-login__tagline">sistem peminjaman laptop sekolah</p>

                <div class="simple-login__info-card">
                    <div class="simple-login__info-item">
                        <i class="bi bi-qr-code-scan"></i>
                        <div>
                            <strong>Scan QR</strong>
                            <span>Mulai pinjam lewat kode QR di laptop</span>
                        </div>
                    </div>
                    <div class="simple-login__info-item">
                        <i class="bi bi-people"></i>
                        <div>
                            <strong>Pinjam Bersama</strong>
                            <span>Anggota kelompok sekelas sekaligus</span>
                        </div>
                    </div>
                    <div class="simple-login__info-item">
                        <i class="bi bi-box-arrow-in-left"></i>
                        <div>
                            <strong>Kembali Cepat</strong>
                            <span>Serahkan kembali dan terverifikasi admin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/auth.js') }}"></script>
</body>
</html>