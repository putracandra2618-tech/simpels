<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard') — Simpels</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

  <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/siswa.css') }}">
  @stack('styles')
</head>

<body class="siswa-body">

  <header class="siswa-topbar">
    <a href="{{ route('dashboard') }}" class="siswa-brand">
      <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="siswa-brand-logo">
      <span>Simpels</span>
    </a>

    <div class="siswa-actions">
      <div class="dropdown">
        <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
          aria-expanded="false" id="btn-notifications" data-bs-auto-close="outside"
          data-notifications-read-url="{{ route('notifications.read') }}">
          <i class="bi bi-bell"></i>
          @if (auth()->user()->unreadNotifications()->count() > 0)
            <span class="navbar-action-badge"></span>
          @endif
        </button>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0"
          aria-labelledby="btn-notifications">
          <div class="notification-header">
            <h6 class="notification-title">Notifikasi</h6>
          </div>
          <div class="notification-list">
            @forelse (auth()->user()->notifications()->take(5)->get() as $notification)
              <a href="{{ data_get($notification->data, 'url', '#') }}" class="notification-item">
                <div class="notification-icon bg-forest-medium text-white">
                  <i class="bi bi-envelope"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text"><strong>{{ data_get($notification->data, 'title') }}</strong><br>{{ data_get($notification->data, 'message') }}</p>
                  <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                @if ($notification->read_at === null)
                  <span class="notification-unread-dot"></span>
                @endif
              </a>
            @empty
              <div class="text-center text-muted py-4">Tidak ada notifikasi.</div>
            @endforelse
          </div>
        </div>
      </div>

      <div class="dropdown ms-2">
        <button class="siswa-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
          aria-expanded="false" id="profile-dropdown">
          <span class="avatar-placeholder avatar-navbar" aria-label="Foto profil">
            <i class="bi bi-person-fill"></i>
          </span>
          <span class="siswa-profile-name d-none d-md-inline">{{ auth()->user()->name }}</span>
          <i class="bi bi-chevron-down navbar-profile-caret"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
          <li class="dropdown-header">Masuk sebagai Siswa</li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </header>

  <div class="siswa-main {{ trim((string) $__env->yieldContent('sticky-actions')) !== '' ? 'has-sticky' : '' }}">

    <div class="page-header">
      <div>
        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        <p class="page-subtitle">@yield('page-subtitle', '')</p>
      </div>
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
          <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif
    </div>

    @if ($errors->any())
      <div class="mb-4">
        @foreach ($errors->all() as $error)
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endforeach
      </div>
    @endif

    @include('layouts.partials.alur-pinjam')

    <main>
      @yield('content')
    </main>

    @hasSection('sticky-actions')
      <div class="siswa-actions-sticky">
        @yield('sticky-actions')
      </div>
    @endif

    <footer class="footer-custom">
      <div class="footer-left">
        <span class="footer-logo">
          <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="footer-logo-img"> Simpels
        </span>
        <span class="footer-separator">|</span>
        <span class="footer-copy">&copy; {{ date('Y') }} Peminjaman Laptop Sekolah</span>
      </div>
    </footer>

  </div>

  <nav class="siswa-bottom-nav" aria-label="Menu siswa">
    <a href="{{ route('dashboard') }}" class="siswa-bottom-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <i class="bi bi-grid-fill"></i>
      <span>Dashboard</span>
    </a>
    <a href="{{ route('pinjam.scan') }}" class="siswa-bottom-link {{ request()->routeIs('pinjam.*') ? 'active' : '' }}">
      <i class="bi bi-qr-code-scan"></i>
      <span>Pinjam</span>
    </a>
    <a href="{{ route('kembali.index') }}" class="siswa-bottom-link {{ request()->routeIs('kembali.*') ? 'active' : '' }}">
      <i class="bi bi-box-arrow-in-left"></i>
      <span>Kembalikan</span>
    </a>
  </nav>

  <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  @stack('scripts')
</body>

</html>