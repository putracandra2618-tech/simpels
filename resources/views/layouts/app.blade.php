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
  <link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
  @stack('styles')
</head>

<body>

  <div class="sidebar-wrapper" id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
      <img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="sidebar-brand-logo">
      <span>Simpels</span>
    </a>

    <div class="flex-grow-1 overflow-y-auto">
      <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Menu</div>
        <ul class="sidebar-menu-list">
          <li class="sidebar-menu-item">
            <a href="{{ route('dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
              <i class="bi bi-grid-fill"></i>
              <span>Dashboard</span>
            </a>
          </li>

          @if (auth()->user()->isAdmin())
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.verifikasi.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.verifikasi*') ? 'active' : '' }}" title="Verifikasi Pengembalian">
                <i class="bi bi-check2-square"></i>
                <span>Verifikasi</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.laptop.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.laptop.*') ? 'active' : '' }}" title="Data Laptop">
                <i class="bi bi-laptop-fill"></i>
                <span>Data Laptop</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.user.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}" title="Data User">
                <i class="bi bi-people-fill"></i>
                <span>Data User</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.adminuser.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.adminuser.*') ? 'active' : '' }}" title="Data Admin">
                <i class="bi bi-person-gear"></i>
                <span>Data Admin</span>
              </a>
            </li>
          @else
            <li class="sidebar-menu-item">
              <a href="{{ route('pinjam.scan') }}" class="sidebar-menu-link {{ request()->routeIs('pinjam.*') ? 'active' : '' }}" title="Pinjam Laptop">
                <i class="bi bi-qr-code-scan"></i>
                <span>Pinjam Laptop</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('kembali.index') }}" class="sidebar-menu-link {{ request()->routeIs('kembali.*') ? 'active' : '' }}" title="Kembalikan Laptop">
                <i class="bi bi-box-arrow-in-left"></i>
                <span>Kembalikan Laptop</span>
              </a>
            </li>
          @endif
        </ul>
      </div>
    </div>

    <div class="sidebar-profile">
      <span class="avatar-placeholder avatar-sidebar" aria-label="Foto profil">
        <i class="bi bi-person-fill"></i>
      </span>
      <div class="sidebar-profile-info">
        <div class="sidebar-profile-name">{{ auth()->user()->name }}</div>
        <div class="sidebar-profile-email">{{ auth()->user()->username }}</div>
      </div>
    </div>
  </div>

  <div class="main-wrapper">

    <header class="navbar-custom">
      <div class="navbar-left">
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
          id="desktop-sidebar-toggle" aria-label="Minimize Sidebar">
          <i class="bi bi-chevron-bar-left"></i>
        </button>
        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Toggle Navigation">
          <i class="bi bi-list"></i>
        </button>
      </div>

      <div class="navbar-search-wrapper d-none d-lg-flex">
        <span class="navbar-search-placeholder">
          <i class="bi bi-info-circle me-1 text-lime"></i>
          Peminjaman Laptop Sekolah
        </span>
      </div>

      <div class="navbar-actions">
        <div class="dropdown">
          <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="btn-notifications" data-bs-auto-close="outside">
            <i class="bi bi-bell"></i>
            @if (auth()->user()->unreadNotifications()->count() > 0)
              <span class="navbar-action-badge"></span>
            @endif
          </button>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0"
            aria-labelledby="btn-notifications">
            <div class="notification-header">
              <h6 class="notification-title">Notifikasi</h6>
              <form method="POST" action="{{ route('notifications.read') }}">
                @csrf
                <button class="btn-clear-all" type="submit">Tandai dibaca</button>
              </form>
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
          <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="profile-dropdown">
            <span class="avatar-placeholder avatar-navbar" aria-label="Foto profil">
              <i class="bi bi-person-fill"></i>
            </span>
            <span class="navbar-profile-name d-none d-md-inline">{{ auth()->user()->name }}</span>
            <i class="bi bi-chevron-down navbar-profile-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
            <li class="dropdown-header">Masuk sebagai {{ auth()->user()->isAdmin() ? 'Admin' : 'Siswa' }}</li>
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

    <main>
      @yield('content')
    </main>

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

  <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  @stack('scripts')
</body>

</html>