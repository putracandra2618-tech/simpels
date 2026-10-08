@extends('layouts.app-siswa')

@section('title', 'Bukti Peminjaman')

@section('pinjam-step', 'bukti')

@section('page-title', 'Bukti Peminjaman')
@section('page-subtitle', 'Simpan atau cetak bukti sebagai arsip peminjaman Anda.')

@push('styles')
  <style>
    @media print {
      body * { visibility: hidden; }
      #receipt, #receipt * { visibility: visible; }
      #receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
      }
      .no-print { display: none !important; }
      .receipt-mobile-list { display: none !important; }
      .receipt-table-wrap { display: block !important; }
    }
  </style>
@endpush

@section('content')
  <div class="no-print mb-3 d-flex justify-content-end">
    <button type="button" class="btn btn-success" onclick="window.print()">
      <i class="bi bi-printer"></i> Cetak Bukti
    </button>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-light shadow-sm" id="receipt">
        <div class="card-header bg-forest-medium text-white">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h4 class="mb-0"><img src="{{ asset('assets/images/logo-simpel.png') }}" alt="Logo Simpels" class="receipt-logo me-1"> Simpels</h4>
              <small>Bukti Peminjaman Laptop Sekolah</small>
            </div>
            <div class="text-end">
              <small>Kode Bukti</small>
              <div class="fw-bold fs-5">PML/PJM/{{ str_pad($borrowing->id, 5, '0', STR_PAD_LEFT) }}</div>
            </div>
          </div>
        </div>

        <div class="p-4">
          <div class="alert alert-success py-2">
            <i class="bi bi-check-circle-fill me-1"></i>
            Peminjaman berhasil dicatat pada {{ $borrowing->borrowed_at->format('d M Y, H:i:s') }}.
          </div>

          <h6 class="text-uppercase text-muted mb-2">Data Laptop</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-4">
              <tbody>
                <tr>
                  <th class="bg-light text-muted" style="width:35%">Nama Laptop</th>
                  <td>{{ $borrowing->laptop->nama }}</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">Merek</th>
                  <td>{{ $borrowing->laptop->merek ?? '-' }}</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">Spesifikasi</th>
                  <td>{!! nl2br(e($borrowing->laptop->spesifikasi ?? '-')) !!}</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">Waktu Peminjaman</th>
                  <td>{{ $borrowing->borrowed_at->format('d M Y, H:i:s') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <h6 class="text-uppercase text-muted mb-2">Anggota Peminjaman ({{ $borrowing->students->count() }}/5)</h6>
          <div class="table-responsive receipt-table-wrap d-none d-md-block">
            <table class="table table-sm table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>No</th>
                  <th>NIS / Username</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th>Jurusan</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($borrowing->students as $index => $student)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->username }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->kelas ?? '-' }}</td>
                    <td>{{ $student->jurusan ?? '-' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <ol class="receipt-mobile-list list-unstyled d-md-none mb-4">
            @foreach ($borrowing->students as $index => $student)
              <li class="receipt-member">
                <span class="receipt-member-no">{{ $index + 1 }}</span>
                <span class="receipt-member-info">
                  <strong>{{ $student->name }}</strong>
                  <span class="text-muted">{{ $student->username }} • {{ $student->kelas ?? '-' }} {{ $student->jurusan ?? '-' }}</span>
                </span>
              </li>
            @endforeach
          </ol>

          <hr>

          <div class="d-flex justify-content-between align-items-end">
            <div class="text-muted small">
              Dicetak pada {{ now()->format('d M Y, H:i:s') }}
            </div>
            <div class="text-center">
              <div class="small text-muted mb-2">Penanggung Jawab</div>
              <div class="text-uppercase fw-bold" style="min-width:120px; padding-top: 30px; border-top: 1px solid #adb5bd;">
                {{ $borrowing->leader?->name }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection