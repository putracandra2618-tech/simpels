@extends('layouts.app')

@section('title', $scope === 'admin' ? 'Data Admin' : 'Data User')

@section('page-title', $scope === 'admin' ? 'Data Admin' : 'Data User')
@section('page-subtitle', $scope === 'admin'
  ? 'Kelola akun admin dan super admin.'
  : 'Kelola akun siswa. Siswa login dengan akun yang dibuat Admin.')

@section('content')
  <div class="table-card-custom">
    <div class="table-header-control">
      <div class="table-filter-group" style="flex-wrap:wrap;">
        @if ($scope === 'admin')
          <select class="form-select form-select-sm" id="filter-role" style="width:auto;">
            <option value="">Role: Semua</option>
            <option value="admin">Admin</option>
            <option value="superadmin">Super Admin</option>
          </select>
        @else
          <select class="form-select form-select-sm" id="filter-kelas" style="width:auto;">
            <option value="">Kelas: Semua</option>
            <option value="X">X</option>
            <option value="XI">XI</option>
            <option value="XII">XII</option>
          </select>
          <select class="form-select form-select-sm" id="filter-jurusan" style="width:auto;">
            <option value="">Jurusan: Semua</option>
            @foreach (['TKJ', 'RPL', 'TEI', 'TPSB', 'TB', 'TKR', 'TP'] as $jurusan)
              <option value="{{ $jurusan }}">{{ $jurusan }}</option>
            @endforeach
          </select>
        @endif
        <div class="table-search-box" style="max-width:260px;">
          <i class="bi bi-search table-search-icon"></i>
          <input type="text" class="table-search-input" id="user-search" placeholder="Cari nama atau username...">
        </div>
      </div>
      <div class="table-filter-group">
        <a href="{{ route($scope === 'admin' ? 'admin.adminuser.create' : 'admin.user.create') }}"
          class="btn-table-action" style="color:#fff; background:#072F1F;">
          <i class="bi bi-person-plus"></i> {{ $scope === 'admin' ? 'Tambah Admin' : 'Tambah User' }}
        </a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table-custom" id="user-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Username</th>
            <th>Role</th>
            @if ($scope !== 'admin')
              <th>Kelas / Jurusan</th>
            @endif
            <th>Peminjaman Aktif</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr class="user-row" data-search="{{ strtolower($user->name.' '.$user->username.' '.$user->role) }}"
              data-role="{{ $user->role }}" data-kelas="{{ $user->kelas }}" data-jurusan="{{ $user->jurusan }}">
              <td>
                <div class="table-user-cell">
                  <span class="avatar-placeholder avatar-table" aria-label="Foto profil">
                    <i class="bi bi-person-fill"></i>
                  </span>
                  <div>
                    <div class="table-user-name">{{ $user->name }}</div>
                    <div class="table-user-sub">{{ $user->isAdmin() ? 'Admin Panel' : 'Portal Siswa' }}</div>
                  </div>
                </div>
              </td>
              <td class="table-product-name">{{ $user->username }}</td>
              <td>
                @if ($user->isSuperAdmin())
                  <span class="badge bg-forest-medium text-white">Super Admin</span>
                @elseif ($user->isAdmin())
                  <span class="badge bg-forest-light text-lime">Admin</span>
                @else
                  <span class="badge-table pending">Siswa</span>
                @endif
              </td>
              @if ($scope !== 'admin')
                <td>
                  @if ($user->kelas || $user->jurusan)
                    {{ $user->kelas ?? '-' }} / {{ $user->jurusan ?? '-' }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              @endif
              <td>
                @if ($user->active_borrowings_count > 0)
                  <span class="badge-table pending">Dipinjam</span>
                @else
                  <span class="badge-table success">Idle</span>
                @endif
              </td>
              <td>
                <div class="d-flex justify-content-center gap-1">
                  <a href="{{ route($scope === 'admin' ? 'admin.adminuser.edit' : 'admin.user.edit', $user) }}" class="table-btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="{{ route($scope === 'admin' ? 'admin.adminuser.destroy' : 'admin.user.destroy', $user) }}"
                    onsubmit="return confirm('Hapus akun {{ $user->name }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $scope === 'admin' ? 5 : 6 }}" class="text-center text-muted py-4">
                {{ $scope === 'admin' ? 'Belum ada akun admin.' : 'Belum ada akun siswa. Klik "Tambah User" untuk mulai.' }}
              </td>
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
      const input = document.getElementById('user-search');
      const filterRole = document.getElementById('filter-role');
      const filterKelas = document.getElementById('filter-kelas');
      const filterJurusan = document.getElementById('filter-jurusan');

      const applyFilters = function () {
        const q = input ? input.value.trim().toLowerCase() : '';
        const role = filterRole ? filterRole.value : '';
        const kelas = filterKelas ? filterKelas.value : '';
        const jurusan = filterJurusan ? filterJurusan.value : '';

        document.querySelectorAll('.user-row').forEach(function (row) {
          const matchesText = (row.dataset.search || '').includes(q);
          const matchesRole = role === '' || row.dataset.role === role;
          const matchesKelas = kelas === '' || row.dataset.kelas === kelas;
          const matchesJurusan = jurusan === '' || row.dataset.jurusan === jurusan;

          row.style.display = matchesText && matchesRole && matchesKelas && matchesJurusan ? '' : 'none';
        });
      };

      if (input) {
        input.addEventListener('input', applyFilters);
      }

      if (filterRole) {
        filterRole.addEventListener('change', applyFilters);
      }

      if (filterKelas) {
        filterKelas.addEventListener('change', applyFilters);
      }

      if (filterJurusan) {
        filterJurusan.addEventListener('change', applyFilters);
      }
    });
  </script>
@endpush