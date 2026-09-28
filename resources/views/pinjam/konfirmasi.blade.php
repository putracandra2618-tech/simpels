@extends('layouts.app-siswa')

@section('title', 'Konfirmasi Pinjam')

@section('pinjam-step', 'anggota')

@section('page-title', 'Konfirmasi Peminjaman')
@section('page-subtitle', 'Periksa informasi laptop lalu pilih anggota peminjaman (maks 5 siswa).')

@section('content')
  <div class="row g-4 justify-content-center">
    <div class="col-lg-5">
      <div class="card border-light shadow-sm h-100">
        <div class="card-header">
          <h2 class="card-title">Info Laptop</h2>
        </div>
        <div class="p-4">
          <img src="{{ $laptop->qrImagePath() }}" alt="QR {{ $laptop->nama }}" class="img-fluid mb-4 d-block mx-auto"
            style="max-width: 180px;">
          <h4 class="mb-1">{{ $laptop->nama }}</h4>
          <p class="text-muted mb-2">{{ $laptop->merek }}</p>
          <dl class="row mb-0 small">
            <dt class="col-4 text-muted">Spesifikasi</dt>
            <dd class="col-8 mb-0">{!! nl2br(e($laptop->spesifikasi ?? '-')) !!}</dd>
            <dt class="col-4 text-muted">Status</dt>
            <dd class="col-8 mb-0"><span class="badge-table success">Tersedia</span></dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <form method="POST" action="{{ route('pinjam.store', $laptop) }}" id="form-konfirmasi">
        @csrf

        <div class="card border-light shadow-sm h-100">
          <div class="card-header">
            <h2 class="card-title">Pilih Anggota Peminjaman</h2>
            <span class="badge bg-forest-light text-lime" id="member-counter">1/5</span>
          </div>

          <div class="p-3">
            <div class="row g-2 mb-3">
              <div class="col-12 col-md-3">
                <select class="form-select form-select-sm" id="filter-kelas">
                  <option value="">Kelas: Semua</option>
                  <option value="X">Kelas X</option>
                  <option value="XI">Kelas XI</option>
                  <option value="XII">Kelas XII</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <select class="form-select form-select-sm" id="filter-jurusan">
                  <option value="">Jurusan: Semua</option>
                  @foreach (['TKJ', 'RPL', 'TEI', 'TPSB', 'TB', 'TKR', 'TP'] as $jurusan)
                    <option value="{{ $jurusan }}">{{ $jurusan }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 col-md-5">
                <div class="table-search-box">
                  <i class="bi bi-search table-search-icon"></i>
                  <input type="text" class="table-search-input" id="member-search" placeholder="Cari NIS, nama, kelas, atau jurusan...">
                </div>
              </div>
            </div>

            <div class="form-check-custom member-item mb-2 border rounded p-2 bg-light-subtle">
              <input class="form-check-input-custom" type="checkbox" id="member-me" checked disabled>
              <label class="form-check-label" for="member-me">
                <strong>{{ $user->name }}</strong>
                <span class="text-muted">— {{ $user->username }} • {{ $user->kelas }} {{ $user->jurusan }}
                  <span class="badge bg-forest-medium text-white ms-1">Anda (scan)</span></span>
              </label>
              <input type="hidden" name="member_ids[]" value="{{ $user->id }}">
            </div>

            <div class="member-list" style="max-height: 360px; overflow-y: auto;">
              @foreach ($students as $student)
                <div class="form-check-custom member-item mb-1 border rounded p-2">
                  <input class="form-check-input-custom member-check" type="checkbox" name="member_ids[]"
                    id="member-{{ $student->id }}" value="{{ $student->id }}"
                    data-search="{{ strtolower($student->username.' '.$student->name.' '.$student->kelas.' '.$student->jurusan) }}"
                    data-kelas="{{ $student->kelas }}" data-jurusan="{{ $student->jurusan }}">
                  <label class="form-check-label" for="member-{{ $student->id }}">
                    <strong>{{ $student->name }}</strong>
                    <span class="text-muted">— {{ $student->username }} • {{ $student->kelas }} {{ $student->jurusan }}</span>
                  </label>
                </div>
              @endforeach
            </div>

            @if ($students->isEmpty())
              <div class="alert alert-warning py-2 mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Tidak ada siswa lain yang tersedia (semua sudah berada di sesi aktif).
              </div>
            @endif
          </div>

          <div class="p-3 border-top d-none d-md-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Maksimal 5 siswa termasuk Anda. Setelah konfirmasi, laptop berstatus Dipinjam.</span>
            <button type="submit" class="btn btn-login px-4 mb-0 w-auto">
              <span>Konfirmasi Pinjam</span>
              <i class="bi bi-arrow-right"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('sticky-actions')
  <div class="siswa-sticky-inner">
    <div class="siswa-sticky-counter small">
      <i class="bi bi-people-fill me-1"></i>
      <strong id="member-counter-bar">1/5</strong>
    </div>
    <button type="submit" form="form-konfirmasi" class="btn btn-login btn-lg mb-0 flex-grow-1">
      <span>Konfirmasi Pinjam</span>
      <i class="bi bi-arrow-right"></i>
    </button>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const counterEl = document.getElementById('member-counter');
      const counterBarEl = document.getElementById('member-counter-bar');
      const max = 5;

      const refreshCounter = function () {
        const checked = document.querySelectorAll('.member-check:checked').length;
        const total = checked + 1;

        if (counterEl) {
          counterEl.textContent = total + '/' + max;
        }

        if (counterBarEl) {
          counterBarEl.textContent = total + '/' + max;
        }

        document.querySelectorAll('.member-check:not(:checked)').forEach(function (cb) {
          cb.disabled = total >= max;
        });
      };

      document.querySelectorAll('.member-check').forEach(function (cb) {
        cb.addEventListener('change', refreshCounter);
      });

      const searchInput = document.getElementById('member-search');
      const filterKelas = document.getElementById('filter-kelas');
      const filterJurusan = document.getElementById('filter-jurusan');

      const applyFilters = function () {
        const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const kelas = filterKelas ? filterKelas.value : '';
        const jurusan = filterJurusan ? filterJurusan.value : '';

        document.querySelectorAll('.member-item').forEach(function (item) {
          const checkbox = item.querySelector('.member-check');
          const term = checkbox.dataset.search || '';
          const matchesText = term.includes(q);
          const matchesKelas = kelas === '' || checkbox.dataset.kelas === kelas;
          const matchesJurusan = jurusan === '' || checkbox.dataset.jurusan === jurusan;

          item.style.display = matchesText && matchesKelas && matchesJurusan ? '' : 'none';
        });
      };

      if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
      }

      if (filterKelas) {
        filterKelas.addEventListener('change', applyFilters);
      }

      if (filterJurusan) {
        filterJurusan.addEventListener('change', applyFilters);
      }

      refreshCounter();
    });
  </script>
@endpush