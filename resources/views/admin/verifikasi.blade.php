@extends('layouts.app')

@section('title', 'Verifikasi Pengembalian')

@section('page-title', 'Verifikasi Pengembalian')
@section('page-subtitle', 'Setujui atau tolak pengembalian laptop yang diajukan siswa.')

@section('content')
  <div class="d-flex justify-content-end mb-3">
    <span class="badge bg-forest-light text-lime">{{ $menungguVerifikasi }} menunggu</span>
  </div>

  <div class="verify-list">
    @forelse ($returnRequests as $returnRequest)
      @php($borrowing = $returnRequest->borrowing)
      <div class="card verify-card">
        <div class="verify-card-top">
          <div>
            <div class="verify-card-title">{{ $borrowing->laptop->nama }}</div>
            <div class="verify-card-meta">
              Diminta {{ $returnRequest->requested_at->format('d M Y, H:i') }}
              &middot; Dipinjam {{ $borrowing->borrowed_at->format('d M Y, H:i') }}
            </div>
          </div>
          <span class="badge-table pending">Menunggu</span>
        </div>

        <div class="verify-card-members">
          <span class="member-badges">
            @foreach ($borrowing->students as $student)
              <span class="badge bg-forest-light text-lime">{{ $student->name }}</span>
            @endforeach
          </span>
        </div>

        <form method="POST" action="{{ route('admin.verifikasi', $returnRequest) }}" class="verify-form row g-2">
          @csrf
          <div class="col-md-4">
            <input type="text" name="condition" class="form-control form-control-sm"
              placeholder="Kondisi laptop (mis. Baik, Rusak ringan)">
          </div>
          <div class="col-md-4">
            <input type="text" name="admin_notes" class="form-control form-control-sm"
              placeholder="Catatan (opsional)">
          </div>
          <div class="col-md-4 d-flex gap-2 align-items-center">
            <button type="submit" name="keputusan" value="disetujui" class="btn btn-sm btn-success flex-fill">
              <i class="bi bi-check-lg"></i> Setujui
            </button>
            <button type="submit" name="keputusan" value="ditolak" class="btn btn-sm btn-outline-danger flex-fill">
              <i class="bi bi-x-lg"></i> Tolak
            </button>
          </div>
        </form>
      </div>
    @empty
      <div class="card verify-card verify-card-empty">
        <i class="bi bi-inbox empty-state-icon"></i>
        <p class="mb-0 text-muted">Tidak ada antrian verifikasi.</p>
      </div>
    @endforelse
  </div>

  @if ($returnRequests->hasPages())
    <div class="table-footer-control">
      <div class="table-pagination-info">
        Menampilkan {{ $returnRequests->firstItem() }}–{{ $returnRequests->lastItem() }} dari {{ $returnRequests->total() }} pengajuan
      </div>
      {{ $returnRequests->links() }}
    </div>
  @endif
@endsection
