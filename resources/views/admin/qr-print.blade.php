<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak QR ({{ $laptops->count() }}) — Simpels</title>
  <link rel="stylesheet" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}">
  <style>
    body { background: #f4f6f6; padding: 24px; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .toolbar { text-align: center; margin-bottom: 20px; }
    .toolbar-hint { font-size: .85rem; color: #6C7E75; margin-top: 10px; }
    .seg-group { display: inline-flex; border: 1px solid #d9e2dd; border-radius: 8px; overflow: hidden; background: #fff; }
    .seg-btn {
      border: 0; background: #fff; color: #072F1F; font-weight: 600;
      padding: 8px 16px; cursor: pointer;
    }
    .seg-btn.active { background: #072F1F; color: #fff; }
    .print-sheet { display: grid; gap: 10px; }
    .print-sheet.count-1 { grid-template-columns: repeat(1, 1fr); }
    .print-sheet.count-4 { grid-template-columns: repeat(2, 1fr); }
    .print-sheet.count-8 { grid-template-columns: repeat(4, 1fr); }
    .sheet-break { page-break-after: always; break-after: page; height: 0; }
    .label-card {
      border: 2px dashed #072F1F;
      border-radius: 12px;
      background: #fff;
      padding: 16px;
      text-align: center;
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .print-sheet.count-1 .label-card { max-width: 320px; margin: 0 auto; }
    .print-sheet.count-1 .label-card img { max-width: 220px; }
    .print-sheet.count-4 .label-card img { max-width: 120px; }
    .print-sheet.count-8 .label-card img { max-width: 90px; }
    .label-card img { width: 100%; height: auto; }
    .label-title { font-weight: 700; font-size: 1rem; color: #072F1F; }
    .label-sub { color: #6C7E75; font-size: .8rem; }
    .label-qr-error { color: #b91c1c; font-size: .8rem; font-weight: 600; }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none !important; }
      .print-sheet { gap: 6mm; }
      .label-card { border: 2px dashed #072F1F; }
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    @page { size: A4 portrait; margin: 6mm; }
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" class="btn btn-success" onclick="window.print()">
      <i class="bi bi-printer"></i> Cetak QR
    </button>
    <a href="{{ route('admin.laptop.index') }}" class="btn btn-outline-secondary">Kembali</a>
    <div class="mt-3">
      <span class="me-2 text-muted">Label per lembar:</span>
      <div class="seg-group" id="count-group">
        <button type="button" class="seg-btn" data-count="1">1</button>
        <button type="button" class="seg-btn" data-count="4">4</button>
        <button type="button" class="seg-btn" data-count="8">8</button>
      </div>
    </div>
    <div class="toolbar-hint">
      <i class="bi bi-info-circle-fill me-1"></i>
      {{ $laptops->count() }} laptop akan dicetak ({{ $laptops->count() === 1 ? 'satu label' : 'label beda-beda' }}).
      Klik Cetak QR, lalu di dialog pilih printer Anda (jangan "Save as PDF").
      Jika hasil terpotong, atur skala "Fit to page" pada pengaturan print Chrome.
    </div>
  </div>

  <div id="print-root"></div>

  <template id="label-template">
    <div class="label-card">
      <img class="label-qr-img" alt="QR" loading="lazy">
      <div class="label-title mt-2"></div>
      <div class="label-sub"></div>
      <div class="label-sub small mt-1">Simpels — Peminjaman Laptop Sekolah</div>
    </div>
  </template>

  <script>
    @php
      $qrPrintData = $laptops->map(fn ($l) => [
          'src' => $l->qrImagePath(),
          'name' => $l->nama,
          'merek' => $l->merek,
      ])->values();
    @endphp
    const laptops = {{ Illuminate\Support\Js::from($qrPrintData) }};

    const root = document.getElementById('print-root');

    function makeLabel(laptop) {
      const clone = document.getElementById('label-template').content.firstElementChild.cloneNode(true);
      const img = clone.querySelector('.label-qr-img');
      img.src = laptop.src;
      img.alt = 'QR ' + laptop.name;
      img.onerror = function () {
        const msg = document.createElement('div');
        msg.className = 'label-qr-error';
        msg.textContent = 'QR gagal dimuat';
        this.replaceWith(msg);
      };
      clone.querySelector('.label-title').textContent = laptop.name;
      clone.querySelector('.label-sub').textContent = laptop.merek;
      return clone;
    }

    function render(count) {
      root.innerHTML = '';
      for (let i = 0; i < laptops.length; i += count) {
        const sheet = document.createElement('div');
        sheet.className = 'print-sheet count-' + count;
        laptops.slice(i, i + count).forEach(function (laptop) {
          sheet.appendChild(makeLabel(laptop));
        });
        root.appendChild(sheet);
        if (i + count < laptops.length) {
          const br = document.createElement('div');
          br.className = 'sheet-break';
          root.appendChild(br);
        }
      }
    }

    document.querySelectorAll('#count-group .seg-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#count-group .seg-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        render(parseInt(btn.dataset.count, 10));
      });
    });

    const defaultCount = laptops.length <= 1 ? 1 : (laptops.length <= 4 ? 4 : 8);
    document.querySelector('#count-group .seg-btn[data-count="' + defaultCount + '"]').classList.add('active');
    render(defaultCount);
  </script>
</body>
</html>