@extends('layouts.app-siswa')

@section('title', 'Dashboard Siswa')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Hai, ' . auth()->user()->name . ' — pantau sesi peminjaman laptop Anda.')

@section('content')
  <div class="row g-4">
    <div class="col-12">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card card-stat h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="card-header">
                <span class="stat-label">Sesi Aktif</span>
              </div>
              <div class="stat-value">{{ $sessionsAktif->count() }}</div>
              <div class="stat-sub">Laptop yang sedang dipinjam</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-stat h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="card-header">
                <span class="stat-label">Selesai Dipinjam</span>
              </div>
              <div class="stat-value">{{ $sessionsSelesai->count() }}</div>
              <div class="stat-sub">Riwayat pengembalian</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          @if ($sessionsAktif->isEmpty())
            <div class="card alert-green-card">
              <div class="position-relative z-index-2">
                <span class="alert-green-badge">Pinjam</span>
                <div class="alert-green-date">Scan QR Code</div>
                <div class="alert-green-text">Tidak ada sesi aktif. Pilih menu Pinjam Laptop lalu scan QR di fisik laptop untuk mulai meminjam.</div>
              </div>
              <a href="{{ route('pinjam.scan') }}" class="alert-green-link z-index-2">
                <span>Mulai Pinjam</span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <svg class="alert-green-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(50,50)">
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" />
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(60)" />
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(120)" />
                </g>
              </svg>
            </div>
          @else
            <div class="card alert-green-card">
              <div class="position-relative z-index-2">
                <span class="alert-green-badge">Aktif</span>
                <div class="alert-green-date">{{ $sessionsAktif->first()->laptop->nama }}</div>
                <div class="alert-green-text">
                  {{ $sessionsAktif->count() }} sesi peminjaman sedang berjalan. Kembalikan laptop untuk mengakhiri sesi.
                </div>
              </div>
              <a href="{{ route('kembali.index') }}" class="alert-green-link z-index-2">
                <span>Kembalikan Laptop</span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <svg class="alert-green-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(50,50)">
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" />
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(60)" />
                  <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(120)" />
                </g>
              </svg>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="card-title">Sesi Peminjaman Aktif</h2>
        </div>
        @if ($sessionsAktif->isEmpty())
          <div class="siswa-empty">
            <i class="bi bi-laptop"></i>
            <h5>Belum ada sesi aktif</h5>
            <p>Scan QR di laptop terdekat untuk mulai meminjam bersama teman.</p>
            <a href="{{ route('pinjam.scan') }}" class="btn btn-login">
              Pinjam Laptop <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        @else
          <div class="transaction-list">
            @foreach ($sessionsAktif as $borrowing)
              <div class="transaction-item">
                <div class="transaction-icon bg-forest-light text-lime">
                  <i class="bi bi-laptop"></i>
                </div>
                <div class="transaction-info">
                  <div class="transaction-name">{{ $borrowing->laptop->nama }}</div>
                  <div class="transaction-date">
                    Aktif sejak {{ $borrowing->borrowed_at->format('d M Y, H:i') }} •
                    {{ $borrowing->students->count() }}/5 siswa
                  </div>
                </div>
                <span class="badge-table {{ $borrowing->status === 'menunggu' ? 'pending' : 'success' }}">
                  {{ strtoupper(str_replace('-', ' ', $borrowing->status)) }}
                </span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    <div class="col-md-5">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="card-title">Riwayat Terakhir</h2>
        </div>
        @if ($sessionsSelesai->isEmpty())
          <div class="siswa-empty">
            <i class="bi bi-clock-history"></i>
            <h5>Belum ada riwayat</h5>
            <p>Riwayat peminjaman yang sudah dikembalikan akan muncul di sini.</p>
          </div>
        @else
          <div class="transaction-list">
            @foreach ($sessionsSelesai->sortByDesc('returned_at')->take(6) as $borrowing)
              <div class="transaction-item">
                <div class="transaction-icon bg-forest-light text-lime">
                  <i class="bi bi-check-circle"></i>
                </div>
                <div class="transaction-info">
                  <div class="transaction-name">{{ $borrowing->laptop->nama }}</div>
                  <div class="transaction-date">
                    Dikembalikan {{ optional($borrowing->returned_at)->format('d M Y, H:i') }}
                  </div>
                </div>
                <span class="badge-table success">Selesai</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection