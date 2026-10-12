@extends('layouts.app')

@section('title', 'Jurusan')

@section('page-title', 'Jurusan')
@section('page-subtitle', 'Kelola pilihan jurusan yang tersedia untuk akun siswa.')

@section('content')
  <div class="table-card-custom">
    <div class="table-header-control">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-semibold">Jurusan</h6>
        <span class="badge bg-forest-light text-lime">{{ $jurusan->count() }} jurusan</span>
      </div>
      <form method="POST" action="{{ $editJurusan ? route('admin.jurusan.update', $editJurusan) : route('admin.jurusan.store') }}"
        class="d-flex gap-2 align-items-center flex-wrap">
        @csrf
        @if ($editJurusan)
          @method('PUT')
        @endif
        @if ($editJurusan)
          <span class="text-muted small">Mengedit "{{ $editJurusan->kode }}"</span>
        @endif
        <input type="text" name="kode" value="{{ old('kode', $editJurusan->kode ?? '') }}" placeholder="Kode, mis. RPL"
          maxlength="255" class="table-search-input" required>
        <input type="text" name="nama" value="{{ old('nama', $editJurusan->nama ?? '') }}" placeholder="Nama jurusan, mis. Rekayasa Perangkat Lunak"
          maxlength="255" class="table-search-input" required>
        <button type="submit" class="btn-table-action btn-table-action-primary">
          <i class="bi {{ $editJurusan ? 'bi-check-lg' : 'bi-plus-lg' }}"></i> {{ $editJurusan ? 'Simpan' : 'Tambah' }}
        </button>
        @if ($editJurusan)
          <a href="{{ route('admin.jurusan.index') }}" class="table-btn-action" title="Batal"><i class="bi bi-x-lg"></i></a>
        @endif
      </form>
    </div>

    <div class="table-responsive">
      <table class="table-custom table-stack" id="jurusan-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama</th>
            <th class="text-center">Jumlah Siswa</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($jurusan as $jurusanItem)
            <tr>
              <td data-label="Kode">
                @if ($editJurusan && $editJurusan->is($jurusanItem))
                  <span class="badge bg-forest-medium text-white me-1">Mengedit</span>
                @endif
                <span class="badge-table">{{ $jurusanItem->kode }}</span>
              </td>
              <td data-label="Nama">{{ $jurusanItem->nama }}</td>
              <td data-label="Jumlah Siswa" class="text-center">{{ $jurusanItem->users_count }} siswa</td>
              <td data-label="Aksi" class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <a href="{{ route('admin.jurusan.index', ['edit' => $jurusanItem->id]) }}" class="table-btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="{{ route('admin.jurusan.destroy', $jurusanItem) }}"
                    onsubmit="return confirm('Hapus jurusan {{ $jurusanItem->kode }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">Belum ada jurusan. Tambahkan lewat form di atas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection