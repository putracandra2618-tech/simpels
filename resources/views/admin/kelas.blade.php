@extends('layouts.app')

@section('title', 'Kelas')

@section('page-title', 'Kelas')
@section('page-subtitle', 'Kelola pilihan kelas yang tersedia untuk akun siswa.')

@section('content')
  <div class="table-card-custom">
    <div class="table-header-control">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-semibold">Kelas</h6>
        <span class="badge bg-forest-light text-lime">{{ $kelas->count() }} tingkatan</span>
      </div>
      <form method="POST" action="{{ $editKelas ? route('admin.kelas.update', $editKelas) : route('admin.kelas.store') }}"
        class="d-flex gap-2 align-items-center">
        @csrf
        @if ($editKelas)
          @method('PUT')
        @endif
        @if ($editKelas)
          <span class="text-muted small">Mengedit "{{ $editKelas->nama }}"</span>
        @endif
        <input type="text" name="nama" value="{{ old('nama', $editKelas->nama ?? '') }}" placeholder="mis. XII" maxlength="255"
          class="table-search-input" required>
        <button type="submit" class="btn-table-action btn-table-action-primary">
          <i class="bi {{ $editKelas ? 'bi-check-lg' : 'bi-plus-lg' }}"></i> {{ $editKelas ? 'Simpan' : 'Tambah' }}
        </button>
        @if ($editKelas)
          <a href="{{ route('admin.kelas.index') }}" class="table-btn-action" title="Batal"><i class="bi bi-x-lg"></i></a>
        @endif
      </form>
    </div>

    <div class="table-responsive">
      <table class="table-custom table-stack" id="kelas-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th class="text-center">Jumlah Siswa</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($kelas as $kelasItem)
            <tr>
              <td data-label="Nama">
                @if ($editKelas && $editKelas->is($kelasItem))
                  <span class="badge bg-forest-medium text-white me-1">Mengedit</span>
                @endif
                {{ $kelasItem->nama }}
              </td>
              <td data-label="Jumlah Siswa" class="text-center">{{ $kelasItem->users_count }} siswa</td>
              <td data-label="Aksi" class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <a href="{{ route('admin.kelas.index', ['edit' => $kelasItem->id]) }}" class="table-btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="{{ route('admin.kelas.destroy', $kelasItem) }}"
                    onsubmit="return confirm('Hapus kelas {{ $kelasItem->nama }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="text-center text-muted py-4">Belum ada kelas. Tambahkan lewat form di atas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection