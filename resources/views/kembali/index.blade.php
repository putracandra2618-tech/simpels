@extends('layouts.app-siswa')

@section('title', 'Kembalikan Laptop')

@section('page-title', 'Kembalikan Laptop')
@section('page-subtitle', 'Ajukan pengembalian laptop yang Anda pinjam. Admin akan memverifikasi.')

@section('content')
  <div class="table-card-custom">
    @if ($borrowings->isEmpty())
      <div class="siswa-empty py-5">
        <i class="bi bi-inbox"></i>
        <h5>Belum ada peminjaman</h5>
        <p>Belum ada laptop yang tercatat atas nama Anda. Scan QR di laptop untuk mulai meminjam.</p>
        <a href="{{ route('pinjam.scan') }}" class="btn btn-login">
          Pinjam Laptop <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table-custom table-kembali">
          <thead>
            <tr>
              <th>Laptop</th>
              <th>Anggota</th>
              <th>Dipinjam</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($borrowings as $borrowing)
              <tr>
                <td data-label="Laptop" class="table-order-id">{{ $borrowing->laptop->nama }}</td>
                <td data-label="Anggota">
                  <span class="member-badges">
                    @foreach ($borrowing->students as $student)
                      <span class="badge bg-forest-light text-lime">{{ $student->name }}</span>
                    @endforeach
                  </span>
                </td>
                <td data-label="Dipinjam">{{ $borrowing->borrowed_at->format('d M Y, H:i') }}</td>
                <td data-label="Status">
                  @if ($borrowing->status === 'aktif')
                    <span class="badge-table success">Dipinjam</span>
                  @elseif ($borrowing->status === 'menunggu')
                    <span class="badge-table pending">Menunggu Verifikasi</span>
                  @else
                    <span class="badge-table failed">Dikembalikan</span>
                  @endif
                </td>
                <td data-label="Aksi">
                  <div class="d-flex flex-column align-items-center gap-2 siswa-table-actions">
                    @if ($borrowing->status === 'aktif')
                      @php $last = $borrowing->latestReturnRequest(); @endphp
                      @if ($last && $last->status === 'ditolak')
                        <div class="small text-center">
                          <span class="text-danger"><i class="bi bi-x-circle me-1"></i>Pengembalian ditolak:</span>
                          <span class="text-muted">{{ $last->admin_notes ?? 'Silakan hubungi Admin.' }}</span>
                        </div>
                      @endif
                      <form method="POST" action="{{ route('kembali.request', $borrowing) }}"
                        onsubmit="return confirm('Ajukan pengembalian laptop {{ $borrowing->laptop->nama }}? Admin akan memverifikasi.');">
                        @csrf
                        <button type="submit" class="btn btn-success">
                          <i class="bi bi-box-arrow-in-left"></i> Kembalikan Laptop
                        </button>
                      </form>
                    @elseif ($borrowing->status === 'menunggu')
                      <span class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Menunggu verifikasi Admin...</span>
                    @else
                      <a href="{{ route('pinjam.bukti', $borrowing) }}" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-text"></i> Lihat Bukti
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection