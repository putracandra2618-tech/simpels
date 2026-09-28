@hasSection('pinjam-step')
  @php
    $steps = [
        'scan' => ['label' => 'Scan QR', 'icon' => 'bi-qr-code-scan'],
        'anggota' => ['label' => 'Pilih Anggota', 'icon' => 'bi-people-fill'],
        'konfirmasi' => ['label' => 'Konfirmasi', 'icon' => 'bi-check2-circle'],
        'bukti' => ['label' => 'Bukti', 'icon' => 'bi-file-earmark-text'],
    ];

    $currentKey = trim((string) $__env->yieldContent('pinjam-step', 'scan'));
    $keys = array_keys($steps);
    $currentIndex = array_search($currentKey, $keys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
  @endphp

  <div class="siswa-steps mb-4" aria-label="Langkah peminjaman">
    <div class="siswa-steps-track">
      @foreach ($keys as $i => $key)
        @php
          $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'active' : 'upcoming');
        @endphp
        <div class="siswa-step {{ $state }}">
          <span class="siswa-step-dot">
            @if ($i < $currentIndex)
              <i class="bi bi-check-lg"></i>
            @else
              <i class="bi {{ $steps[$key]['icon'] }}"></i>
            @endif
          </span>
          <span class="siswa-step-label">{{ $steps[$key]['label'] }}</span>
        </div>
      @endforeach
    </div>
    <div class="siswa-steps-caption">
      Langkah {{ $currentIndex + 1 }} dari {{ count($keys) }}:
      <strong>{{ $steps[$currentKey]['label'] }}</strong>
    </div>
  </div>
@endif