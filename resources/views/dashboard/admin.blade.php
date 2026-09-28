@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Pantau peminjaman laptop dan verifikasi pengembalian.')

@section('content')
  <div class="row g-4">
    <div class="col-12">
      <div class="row g-4">
        @php
          $stats = [
            ['label' => 'Total Laptop', 'value' => $totalLaptops, 'sub' => 'Seluruh laptop terdaftar', 'up' => true],
            ['label' => 'Tersedia', 'value' => $laptopsTersedia, 'sub' => 'Siap dipinjam', 'up' => true],
            ['label' => 'Dipinjam', 'value' => $laptopsDipinjam, 'sub' => 'Sedang dipakai sesi', 'up' => false],
            ['label' => 'Menunggu Verifikasi', 'value' => $menungguVerifikasi, 'sub' => 'Permintaan pengembalian', 'up' => false],
          ];
        @endphp
        @foreach ($stats as $stat)
          <div class="col-md-3 col-sm-6">
            <div class="card card-stat">
              <div>
                <div class="card-header">
                  <span class="stat-label">{{ $stat['label'] }}</span>
                </div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="trend-badge {{ $stat['up'] ? 'trend-up' : 'trend-down' }}">
                  <span>{{ $stat['sub'] }}</span>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="col-xl-8 col-lg-7">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="card-title">Antrian Verifikasi Pengembalian</h2>
          <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.verifikasi.index') }}" class="text-decoration-none text-muted-green small">Lihat Halaman →</a>
            <a href="{{ route('admin.laptop.index') }}" class="text-decoration-none text-muted-green small">Kelola Laptop →</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Laptop</th>
                <th>Anggota</th>
                <th>Diminta</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($returnRequests as $returnRequest)
                <tr>
                  <td class="fw-semibold">{{ $returnRequest->borrowing->laptop->nama }}</td>
                  <td>
                    @foreach ($returnRequest->borrowing->students->take(2) as $student)
                      <span class="badge bg-forest-light text-lime me-1">{{ $student->name }}</span>
                    @endforeach
                    @if ($returnRequest->borrowing->students->count() > 2)
                      <span class="text-muted small">+{{ $returnRequest->borrowing->students->count() - 2 }} lainnya</span>
                    @endif
                  </td>
                  <td>{{ $returnRequest->requested_at->format('d M Y, H:i') }}</td>
                  <td>
                    <div class="d-flex justify-content-center gap-1">
                      <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-check-circle"></i> Verifikasi
                      </a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Tidak ada antrian verifikasi.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @foreach ($returnRequests as $returnRequest)
          <div id="verifikasi-{{ $returnRequest->id }}" class="p-3 border-top">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <strong>{{ $returnRequest->borrowing->laptop->nama }}</strong>
                <div class="text-muted small">
                  Dipinjam {{ $returnRequest->borrowing->borrowed_at->format('d M Y, H:i') }} oleh
                  {{ $returnRequest->borrowing->students->pluck('name')->join(', ') }}.
                </div>
              </div>
              <span class="badge-table pending">Menunggu</span>
            </div>
            <form method="POST" action="{{ route('admin.verifikasi', $returnRequest) }}" class="row g-2">
              @csrf
              <input type="hidden" name="keputusan" value="disetujui" id="keputusan-{{ $returnRequest->id }}">
              <div class="col-md-4">
                <input type="text" name="condition" class="form-control form-control-sm" placeholder="Kondisi laptop (mis. Baik, Rusak ringan)">
              </div>
              <div class="col-md-4">
                <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Catatan (opsional)">
              </div>
              <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-success flex-fill"
                  onclick="document.getElementById('keputusan-{{ $returnRequest->id }}').value='disetujui';">
                  <i class="bi bi-check-lg"></i> Setujui
                </button>
                <button type="submit" class="btn btn-sm btn-outline-danger flex-fill"
                  onclick="document.getElementById('keputusan-{{ $returnRequest->id }}').value='ditolak';">
                  <i class="bi bi-x-lg"></i> Tolak
                </button>
              </div>
            </form>
          </div>
        @endforeach
      </div>
    </div>

    <div class="col-xl-4 col-lg-5">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="card-title">Peminjaman Terbaru</h2>
        </div>
        <div class="transaction-list">
          @forelse ($recentBorrowings as $borrowing)
            <div class="transaction-item">
              <div class="transaction-icon bg-forest-light text-lime">
                <i class="bi bi-laptop"></i>
              </div>
              <div class="transaction-info">
                <div class="transaction-name">{{ $borrowing->laptop->nama }}</div>
                <div class="transaction-date">
                  {{ $borrowing->students->pluck('name')->take(2)->join(', ') }} •
                  {{ $borrowing->borrowed_at->format('d M, H:i') }}
                </div>
              </div>
              <span class="badge-table {{ $borrowing->status === 'aktif' ? 'success' : ($borrowing->status === 'menunggu' ? 'pending' : 'failed') }}">
                {{ strtoupper($borrowing->status) }}
              </span>
            </div>
          @empty
            <div class="text-center text-muted py-5">Belum ada peminjaman.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection