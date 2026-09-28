@extends('layouts.app')

@section('title', 'Verifikasi Pengembalian')

@section('page-title', 'Verifikasi Pengembalian')
@section('page-subtitle', 'Setujui atau tolak pengembalian laptop yang diajukan siswa.')

@section('content')
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Antrian Verifikasi</h2>
          <span class="badge bg-forest-light text-lime">{{ $menungguVerifikasi }} menunggu</span>
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
                      <a href="#verifikasi-{{ $returnRequest->id }}" class="btn btn-sm btn-outline-success"
                         onclick="document.getElementById('verifikasi-{{ $returnRequest->id }}').scrollIntoView({behavior:'smooth'}); return false;">
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
  </div>
@endsection