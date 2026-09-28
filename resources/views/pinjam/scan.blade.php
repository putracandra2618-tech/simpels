@extends('layouts.app-siswa')

@section('title', 'Pinjam Laptop')

@section('pinjam-step', 'scan')

@section('page-title', 'Pinjam Laptop')
@section('page-subtitle', 'Scan QR Code pada fisik laptop yang ingin dipinjam (maks 5 siswa per laptop).')

@section('content')
  <div class="row g-4 justify-content-center">
    <div class="col-lg-6">
      <div class="card border-light shadow-sm text-center">
        <div class="mb-3">
          <div class="bootsh mb-3 text-lime empty-state-icon" style="font-size: 2.5rem;">
            <i class="bi bi-qr-code-scan"></i>
          </div>
          <h5 class="mb-1">Arahkan Kamera ke QR Code Laptop</h5>
          <p class="text-muted mb-0">Pastikan QR terlihat jelas di dalam bingkai dan tidak silau.</p>
        </div>

        <div id="qr-reader" class="siswa-scan-frame mx-auto mb-3"></div>

        <div id="scan-status" class="small text-muted mb-3">
          <i class="bi bi-camera-video me-1"></i> Kamera belum aktif.
        </div>

        <div class="row g-2 siswa-scan-actions">
          <div class="col-12 col-sm-6">
            <button type="button" class="btn btn-success btn-lg w-100" id="btn-start-scan">
              <i class="bi bi-camera-video"></i> Mulai Scan
            </button>
          </div>
          <div class="col-12 col-sm-6">
            <button type="button" class="btn btn-outline-secondary btn-lg w-100" id="btn-stop-scan" disabled>
              <i class="bi bi-stop-circle"></i> Stop
            </button>
          </div>
        </div>

        <form method="POST" action="{{ route('pinjam.validasi') }}" id="form-validasi" class="d-none">
          @csrf
          <input type="hidden" name="qr_token" id="qr-token" value="">
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('assets/libs/html5-qrcode/html5-qrcode.min.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const readerElement = document.getElementById('qr-reader');
      const statusEl = document.getElementById('scan-status');
      const startBtn = document.getElementById('btn-start-scan');
      const stopBtn = document.getElementById('btn-stop-scan');

      if (typeof Html5Qrcode === 'undefined') {
        statusEl.textContent = 'Library scanner tidak termuat.';
        return;
      }

      const html5QrCode = new Html5Qrcode('qr-reader');
      const config = { fps: 10, qrbox: { width: 260, height: 260 } };

      let scanning = false;

      const onScanSuccess = function (decodedText) {
        if (!scanning) {
          return;
        }

        statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> QR terbaca! Memproses...';
        document.getElementById('qr-token').value = decodedText;
        document.getElementById('form-validasi').submit();
      };

      const onScanFailure = function () {
        // Diam; scanner terus mencoba membaca QR.
      };

      startBtn.addEventListener('click', function () {
        html5QrCode.start({ facingMode: 'environment' }, config, onScanSuccess, onScanFailure)
          .then(function () {
            scanning = true;
            statusEl.innerHTML = '<i class="bi bi-camera-video-fill text-success me-1"></i> Kamera aktif, arahkan ke QR laptop.';
            startBtn.disabled = true;
            stopBtn.disabled = false;
          })
          .catch(function () {
            statusEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Kamera tidak dapat diakses. Pastikan izin kamera diberikan.';
          });
      });

      stopBtn.addEventListener('click', function () {
        html5QrCode.stop().then(function () {
          scanning = false;
          statusEl.textContent = 'Kamera dihentikan.';
          startBtn.disabled = false;
          stopBtn.disabled = true;
        }).catch(function () {});
      });

      if (window.location.hash === '#start') {
        startBtn.click();
      }
    });
  </script>
@endpush