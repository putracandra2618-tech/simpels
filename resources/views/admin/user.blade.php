@extends('layouts.app')

@section('title', $scope === 'admin' ? 'Data Admin' : 'Data User')

@section('page-title', $scope === 'admin' ? 'Data Admin' : 'Data User')
@section('page-subtitle', $scope === 'admin'
  ? 'Kelola akun admin dan super admin.'
  : 'Kelola akun siswa. Siswa login dengan akun yang dibuat Admin.')

@section('content')
  <div class="table-card-custom">
    <form method="GET" action="{{ route($scope === 'admin' ? 'admin.adminuser.index' : 'admin.user.index') }}" class="table-header-control" data-filter-form>
      <div class="table-filter-group">
        @if ($scope === 'admin')
          <select class="form-select form-select-sm" id="filter-role" name="role">
            <option value="">Role: Semua</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            <option value="superadmin" @selected(request('role') === 'superadmin')>Super Admin</option>
          </select>
        @else
          <select class="form-select form-select-sm" id="filter-kelas" name="kelas">
            <option value="">Kelas: Semua</option>
            <option value="X" @selected(request('kelas') === 'X')>X</option>
            <option value="XI" @selected(request('kelas') === 'XI')>XI</option>
            <option value="XII" @selected(request('kelas') === 'XII')>XII</option>
          </select>
          <select class="form-select form-select-sm" id="filter-jurusan" name="jurusan">
            <option value="">Jurusan: Semua</option>
            @foreach (['TKJ', 'RPL', 'TEI', 'TPSB', 'TB', 'TKR', 'TP'] as $jurusan)
              <option value="{{ $jurusan }}" @selected(request('jurusan') === $jurusan)>{{ $jurusan }}</option>
            @endforeach
          </select>
        @endif
        <div class="table-search-box">
          <i class="bi bi-search table-search-icon"></i>
          <input type="text" name="q" value="{{ request('q') }}" class="table-search-input" id="user-search" placeholder="Cari nama atau username...">
        </div>
      </div>
      <div class="table-filter-group">
        <a href="{{ route($scope === 'admin' ? 'admin.adminuser.create' : 'admin.user.create') }}"
          class="btn-table-action btn-table-action-primary">
          <i class="bi bi-person-plus"></i> {{ $scope === 'admin' ? 'Tambah Admin' : 'Tambah User' }}
        </a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table-custom table-stack" id="user-table">
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
            <tr class="user-row">
              <td data-label="User">
                <div class="table-user-cell">
                  <div>
                    <div class="table-user-name">{{ $user->name }}</div>
                    <div class="table-user-sub">{{ $user->isAdmin() ? 'Admin Panel' : 'Portal Siswa' }}</div>
                  </div>
                </div>
              </td>
              <td class="table-product-name" data-label="Username">{{ $user->username }}</td>
              <td data-label="Role">
                @if ($user->isSuperAdmin())
                  <span class="badge bg-forest-medium text-white">Super Admin</span>
                @elseif ($user->isAdmin())
                  <span class="badge bg-forest-light text-lime">Admin</span>
                @else
                  <span class="badge-table pending">Siswa</span>
                @endif
              </td>
              @if ($scope !== 'admin')
                <td data-label="Kelas / Jurusan">
                  @if ($user->kelas || $user->jurusan)
                    {{ $user->kelas ?? '-' }} / {{ $user->jurusan ?? '-' }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              @endif
              <td data-label="Peminjaman Aktif">
                @if ($user->active_borrowings_count > 0)
                  <span class="badge-table pending">Dipinjam</span>
                @else
                  <span class="badge-table success">Idle</span>
                @endif
              </td>
              <td data-label="Aksi">
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

    @if ($users->hasPages())
      <div class="table-footer-control">
        <div class="table-pagination-info">
          Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} akun
        </div>
        {{ $users->links() }}
      </div>
    @endif
  </div>
@endsection