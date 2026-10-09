@extends('layouts.app')

@section('title', 'Data Laptop')

@section('page-title', 'Data Laptop')
@section('page-subtitle', 'Kelola data laptop dan cetak QR Code untuk ditempel di fisik laptop.')

@section('content')
  <div class="table-card-custom">
    <div class="table-header-control">
      <div class="table-filter-group" style="flex-wrap:wrap;">
        <div class="table-search-box" style="max-width:260px;">
          <i class="bi bi-search table-search-icon"></i>
          <input type="text" class="table-search-input" id="laptop-search" placeholder="Cari nama atau merek laptop...">
        </div>
        <button type="button" class="btn-table-action" id="btn-print-selected"
          style="color:#fff; background:#072F1F;" disabled>
          <i class="bi bi-qr-code"></i> Cetak QR Terpilih
        </button>
      </div>
      <div class="table-filter-group">
        <a href="{{ route('admin.laptop.create') }}" class="btn-table-action" style="color:#fff; background:#072F1F;">
          <i class="bi bi-plus-lg"></i> Tambah Laptop
        </a>
      </div>
    </div>
 
    <div class="table-responsive">
      <table class="table-custom table-stack" id="laptop-table">
        <thead>
          <tr>
            <th class="text-center"><input type="checkbox" id="check-all" title="Pilih semua"></th>
            <th>QR</th>
            <th>Laptop</th>
            <th>Spesifikasi</th>
            <th>Status</th>
            <th>Siapa yang Pinjam</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($laptops as $laptop)
            @php
              $active = $laptop->activeBorrowings->sortByDesc('borrowed_at')->first();
              $count = $active ? $active->students->count() : 0;
            @endphp
            <tr class="laptop-row" data-search="{{ strtolower($laptop->nama.' '.$laptop->merek) }}">
              <td class="text-center" data-label="Pilih">
                <input type="checkbox" class="laptop-check" value="{{ $laptop->id }}" aria-label="Pilih {{ $laptop->nama }}">
              </td>
              <td data-label="QR">
                <img src="{{ $laptop->qrImagePath() }}" alt="QR {{ $laptop->nama }}" width="56" height="56"
                  onerror="this.src='{{ asset('assets/images/avatar.png') }}'">
              </td>
              <td data-label="Laptop">
                <div class="table-user-cell">
                  <div>
                    <div class="table-user-name">{{ $laptop->nama }}</div>
                    <div class="table-user-sub">{{ $laptop->merek ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td class="table-product-name" data-label="Spesifikasi">{{ Str::limit($laptop->spesifikasi ?? '-', 60) }}</td>
              <td data-label="Status">
                @if ($laptop->status === 'dipinjam')
                  <span class="badge-table pending">Dipinjam {{ $count }}/5</span>
                @else
                  <span class="badge-table success">Tersedia</span>
                @endif
              </td>
              <td class="member-cell" data-label="Peminjam">
                @if ($active)
                  <span class="member-badges">
                    @foreach ($active->students as $student)
                      <span class="badge bg-forest-light text-lime">{{ $student->name }}</span>
                    @endforeach
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td data-label="Aksi">
                <div class="d-flex justify-content-center gap-1">
                  <a href="{{ route('admin.laptop.qr', $laptop) }}" class="table-btn-action" title="Cetak QR"><i class="bi bi-qr-code"></i></a>
                  <a href="{{ route('admin.laptop.edit', $laptop) }}" class="table-btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="{{ route('admin.laptop.destroy', $laptop) }}"
                    onsubmit="return confirm('Hapus laptop {{ $laptop->nama }}? Tindakan ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Belum ada data laptop. Klik "Tambah Laptop" untuk mulai.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const input = document.getElementById('laptop-search');
      const checkAll = document.getElementById('check-all');
      const btnPrint = document.getElementById('btn-print-selected');
      const root = '{{ route('admin.laptop.print') }}';

      function visibleRows() {
        return Array.from(document.querySelectorAll('.laptop-row'))
          .filter(function (row) { return row.style.display !== 'none'; });
      }

      function updatePrintButton() {
        const ids = Array.from(document.querySelectorAll('.laptop-check:checked'))
          .map(function (c) { return c.value; });
        btnPrint.disabled = ids.length === 0;
        btnPrint.textContent = ids.length === 0
          ? 'Cetak QR Terpilih'
          : 'Cetak QR Terpilih (' + ids.length + ')';
      }

      if (input) {
        input.addEventListener('input', function () {
          const q = input.value.trim().toLowerCase();
          visibleRows();
          document.querySelectorAll('.laptop-row').forEach(function (row) {
            row.style.display = (row.dataset.search || '').includes(q) ? '' : 'none';
          });
          if (checkAll) {
            checkAll.disabled = visibleRows().length === 0;
            checkAll.checked = false;
          }
          updatePrintButton();
        });
      }

      if (checkAll) {
        checkAll.addEventListener('change', function () {
          visibleRows().forEach(function (row) {
            row.querySelector('.laptop-check').checked = checkAll.checked;
          });
          updatePrintButton();
        });
      }

      document.querySelectorAll('.laptop-check').forEach(function (c) {
        c.addEventListener('change', updatePrintButton);
      });

      if (btnPrint) {
        btnPrint.addEventListener('click', function () {
          const ids = Array.from(document.querySelectorAll('.laptop-check:checked'))
            .map(function (c) { return c.value; });
          if (ids.length > 0) {
            window.location.href = root + '?ids=' + ids.join(',');
          }
        });
      }
    });
  </script>
@endpush