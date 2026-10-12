@extends('layouts.app')

@php
  $isAdminScope = ($scope ?? 'siswa') === 'admin';
@endphp

@section('title', $user ? 'Edit '.($isAdminScope ? 'Admin' : 'User') : 'Tambah '.($isAdminScope ? 'Admin' : 'User'))

@section('page-title', $user ? 'Edit '.($isAdminScope ? 'Admin' : 'User') : 'Tambah '.($isAdminScope ? 'Admin' : 'User'))
@section('page-subtitle', $user
  ? 'Perbarui data akun. Kosongkan password jika tidak diganti.'
  : ($isAdminScope ? 'Buat akun admin.' : 'Buat akun siswa baru. Siswa login dengan kredensial ini.'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-light shadow-sm p-4">
        <form method="POST"
          action="{{ $user
            ? route($isAdminScope ? 'admin.adminuser.update' : 'admin.user.update', $user)
            : route($isAdminScope ? 'admin.adminuser.store' : 'admin.user.store') }}">
          @csrf
          @if ($user)
            @method('PUT')
          @endif

          <div class="mb-3">
            <label for="name" class="form-label-custom">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control-custom @error('name') is-invalid-custom @enderror" id="name"
              name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="mis. Ahmad Fauzi" required>
            @error('name')
              <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="username" class="form-label-custom">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control-custom @error('username') is-invalid-custom @enderror" id="username"
              name="username" value="{{ old('username', $user->username ?? '') }}" placeholder="mis. 20240001" required>
            @error('username')
              <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
            @enderror
          </div>

          @if ($isAdminScope)
            <div class="mb-3">
              <label for="role" class="form-label-custom">Role <span class="text-danger">*</span></label>
              <select class="form-select-custom @error('role') is-invalid-custom @enderror" id="role" name="role" required>
                <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')>Admin</option>
                @if (auth()->user()->isSuperAdmin())
                  <option value="superadmin" @selected(old('role', $user->role ?? '') === 'superadmin')>Super Admin</option>
                @endif
              </select>
              @error('role')
                <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
              @enderror
            </div>
          @else
            <input type="hidden" name="role" value="siswa">
            <div class="row g-3" id="kelas-jurusan">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="kelas_id" class="form-label-custom">Kelas <span class="text-danger">*</span></label>
                  <select class="form-select-custom @error('kelas_id') is-invalid-custom @enderror" id="kelas_id" name="kelas_id" required>
                    <option value="" disabled {{ old('kelas_id', $user->kelas_id ?? '') === null ? 'selected' : '' }}>Pilih Kelas…</option>
                    @foreach ($kelas as $kelasItem)
                      <option value="{{ $kelasItem->id }}" @selected((string) old('kelas_id', $user->kelas_id ?? '') === (string) $kelasItem->id)>{{ $kelasItem->nama }}</option>
                    @endforeach
                  </select>
                  @error('kelas_id')
                    <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="jurusan_id" class="form-label-custom">Jurusan <span class="text-danger">*</span></label>
                  <select class="form-select-custom @error('jurusan_id') is-invalid-custom @enderror" id="jurusan_id" name="jurusan_id" required>
                    <option value="" disabled {{ old('jurusan_id', $user->jurusan_id ?? '') === null ? 'selected' : '' }}>Pilih Jurusan…</option>
                    @foreach ($jurusan as $jurusanItem)
                      <option value="{{ $jurusanItem->id }}" @selected((string) old('jurusan_id', $user->jurusan_id ?? '') === (string) $jurusanItem->id)>{{ $jurusanItem->kode }}</option>
                    @endforeach
                  </select>
                  @error('jurusan_id')
                    <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>
          @endif

          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="password" class="form-label-custom">
                  Password @if (!$user)<span class="text-danger">*</span>@endif
                </label>
                <input type="password" class="form-control-custom @error('password') is-invalid-custom @enderror" id="password"
                  name="password" placeholder="{{ $user ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter' }}">
                @error('password')
                  <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="password_confirmation" class="form-label-custom">Konfirmasi Password</label>
                <input type="password" class="form-control-custom @error('password') is-invalid-custom @enderror" id="password_confirmation"
                  name="password_confirmation">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route($isAdminScope ? 'admin.adminuser.index' : 'admin.user.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-login px-4">
              <span>{{ $user ? 'Simpan Perubahan' : 'Buat Akun' }}</span>
              <i class="bi bi-check-lg"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection