@extends('layouts.app')

@section('title', $laptop ? 'Edit Laptop' : 'Tambah Laptop')

@section('page-title', $laptop ? 'Edit Laptop' : 'Tambah Laptop')
@section('page-subtitle', $laptop ? 'Perbarui data laptop. QR Code akan diperbarui otomatis.' : 'Tambah laptop baru. QR Code dibuat otomatis untuk ditempel di fisik laptop.')

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-light shadow-sm p-4">
        <form method="POST"
          action="{{ $laptop ? route('admin.laptop.update', $laptop) : route('admin.laptop.store') }}">
          @csrf
          @if ($laptop)
            @method('PUT')
          @endif

          <div class="mb-3">
            <label for="nama" class="form-label-custom">Nama Laptop <span class="text-danger">*</span></label>
            <input type="text" class="form-control-custom @error('nama') is-invalid-custom @enderror" id="nama"
              name="nama" value="{{ old('nama', $laptop->nama ?? '') }}" placeholder="mis. Lenovo ThinkPad T14" required>
            @error('nama')
              <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="merek" class="form-label-custom">Merek</label>
            <input type="text" class="form-control-custom @error('merek') is-invalid-custom @enderror" id="merek"
              name="merek" value="{{ old('merek', $laptop->merek ?? '') }}" placeholder="mis. Lenovo, Asus, Dell">
            @error('merek')
              <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="spesifikasi" class="form-label-custom">Spesifikasi</label>
            <textarea class="form-control-custom @error('spesifikasi') is-invalid-custom @enderror" id="spesifikasi"
              name="spesifikasi" rows="4" placeholder="Prosesor, RAM, penyimpanan, dll.">{{ old('spesifikasi', $laptop->spesifikasi ?? '') }}</textarea>
            @error('spesifikasi')
              <div class="form-feedback-custom invalid-custom"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
            @enderror
          </div>

          @if ($laptop)
            <div class="mb-3">
              <label class="form-label-custom">QR Code Saat Ini</label>
              <div>
                <img src="{{ $laptop->qrImagePath() }}" alt="QR {{ $laptop->nama }}" width="120"
                  onerror="this.src='{{ asset('assets/images/avatar.png') }}'">
                <a href="{{ route('admin.laptop.qr', $laptop) }}" class="ms-3"><i class="bi bi-printer"></i> Cetak QR</a>
              </div>
            </div>
          @endif

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.laptop.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-login px-4">
              <span>{{ $laptop ? 'Simpan Perubahan' : 'Simpan & Buat QR' }}</span>
              <i class="bi bi-check-lg"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection