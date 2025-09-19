@extends('layouts.app')

@section('content')
  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="text-muted small">Form</div>
      <h5 class="mb-0">Buat Tagihan</h5>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-ghost round">Kembali</a>
  </div>

  <form method="POST" action="{{ route('tagihan.store') }}" id="formTagihan">
    @csrf
    <div class="row g-3 form--soft">
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-body">
            <div class="h6 mb-3">Informasi Utama</div>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Sekolah</label>
                <select name="master_sekolah_id" id="master_sekolah_id" class="form-select" required>
                  @php $selected = old('master_sekolah_id', $prefillId ?? null); @endphp
                  @foreach($sekolah as $s)
                    <option value="{{ $s->id }}" data-siswa="{{ $s->jumlah_siswa ?? 0 }}"
                      {{ (string)$selected === (string)$s->id ? 'selected' : '' }}>
                      {{ $s->nama_sekolah }}
                    </option>
                  @endforeach
                </select>
                @error('master_sekolah_id')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Nomor Tagihan</label>
                <input name="nomor" class="form-control" value="{{ old('nomor') }}" placeholder="Kosongkan untuk auto">
                @error('nomor')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Tanggal Tagihan</label>
                <input type="date" name="tanggal_tagihan" class="form-control"
                       value="{{ old('tanggal_tagihan', now()->toDateString()) }}">
                @error('tanggal_tagihan')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              {{-- === OPSI AUTO HITUNG === --}}
              <div class="col-12">
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" id="hitung_otomatis" name="hitung_otomatis" value="1"
                             {{ old('hitung_otomatis') ? 'checked' : '' }}>
                  <label class="form-check-label" for="hitung_otomatis">
                    Hitung otomatis dari harga modul × jumlah siswa
                  </label>
                </div>
                <div class="small text-muted">Centang untuk mengisi Total secara otomatis.</div>
              </div>

              <div id="autoCalcArea" class="col-12" style="display:none;">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Jumlah Siswa</label>
                    <input type="number" min="0" class="form-control" id="siswa_count" name="siswa_count"
                               value="{{ old('siswa_count', $defaultSiswa ?? 0) }}">
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Modul Dihitung</label>
                    <div id="modList" class="border rounded p-2" style="max-height:160px; overflow:auto;">
                      {{-- server-side render awal (tetap boleh) --}}
                      @php $oldMods = collect(old('modul_ids', []))->map('intval')->all(); @endphp
                      @forelse(($assigned ?? collect()) as $m)
                        <div class="form-check">
                          <input class="form-check-input modul-check" type="checkbox" name="modul_ids[]"
                                     value="{{ $m['id'] }}"
                                     {{ empty($oldMods) ? 'checked' : (in_array($m['id'],$oldMods) ? 'checked' : '') }}>
                          <label class="form-check-label">{{ $m['nama'] }}</label>
                        </div>
                      @empty
                        <div class="small text-muted">Belum ada modul terpasang pada sekolah ini.</div>
                      @endforelse
                    </div>
                    <div class="form-text">Bisa centang/untick modul yang mau diikutkan perhitungan.</div>
                  </div>

                  <div class="col-12">
                    <div class="card elev-1">
                      <div class="card-body">
                        <div class="h6 mb-2">Preview Perhitungan</div>
                        <div id="calcPreview" class="small text-muted">Aktifkan dan ubah parameter untuk melihat preview.</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {{-- === END OPSI AUTO HITUNG === --}}

              <div class="col-md-6">
                <label class="form-label">Total</label>
                <input type="number" name="total" id="total" class="form-control"
                       value="{{ old('total') }}" min="0" {{ old('hitung_otomatis') ? 'readonly' : '' }} required>
                @error('total')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Terbayar (opsional)</label>
                <input type="number" name="terbayar" class="form-control" value="{{ old('terbayar',0) }}" min="0">
                @error('terbayar')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                @error('catatan')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="h6 mb-3">Opsi</div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Jatuh Tempo</label>
                <input type="date" name="jatuh_tempo" class="form-control"
                       value="{{ old('jatuh_tempo') }}" required>
                @error('jatuh_tempo')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  @foreach(['draft','sebagian','lunas'] as $st)
                    <option value="{{ $st }}" {{ old('status','draft')===$st ? 'selected' : '' }}>
                      {{ ucfirst($st) }}
                    </option>
                  @endforeach
                </select>
                @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" id="buat_lagi" name="buat_lagi" value="1"
                             {{ old('buat_lagi', request('buat_lagi')) ? 'checked' : '' }}>
                  <label class="form-check-label" for="buat_lagi">
                    Buat tagihan lagi untuk sekolah ini
                  </label>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="position-sticky bottom-0 mt-3" style="z-index:10;">
      <div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="text-muted small">Periksa lagi sebelum menyimpan.</div>
        <div class="d-flex gap-2">
          <a href="{{ route('tagihan.index') }}" class="btn btn-ghost round">Batal</a>
          <button class="btn btn-primary round">Simpan</button>
        </div>
      </div>
    </div>
  </form>

  {{-- JS kecil untuk auto hitung & preview --}}
  <script>
    const elHitung    = document.getElementById('hitung_otomatis');
    const areaAuto    = document.getElementById('autoCalcArea');
    const elSiswa     = document.getElementById('siswa_count');
    const elSekolah   = document.getElementById('master_sekolah_id');
    const elTotal     = document.getElementById('total');
    const preview     = document.getElementById('calcPreview');
    const modList     = document.getElementById('modList');

    function renderModList(items) {
      if (!items || items.length === 0) {
        modList.innerHTML = '<div class="small text-muted">Belum ada modul terpasang pada sekolah ini.</div>';
        return;
      }
      // default: semua di-check
      let html = items.map(it => `
        <div class="form-check">
          <input class="form-check-input modul-check" type="checkbox" name="modul_ids[]"
                     value="${it.id}" checked>
          <label class="form-check-label">${it.nama}</label>
        </div>
      `).join('');
      modList.innerHTML = html;
    }

    async function fetchAssigned() {
      const masterId = elSekolah.value;
      if (!masterId) return;

      // hanya load saat opsi auto hitung aktif (biar hemat)
      if (!elHitung.checked) return;

      try {
        const res  = await fetch(`{{ route('tagihan.assigned') }}?master_sekolah_id=${masterId}`);
        const json = await res.json();
        if (!json.ok) throw new Error('failed');

        renderModList(json.items || []);
        // setelah list modul terisi, hitung ulang
        refreshPreview();
      } catch (e) {
        modList.innerHTML = '<div class="text-danger small">Gagal memuat daftar modul.</div>';
      }
    }

    function getCheckedModulIds() {
      return Array.from(document.querySelectorAll('.modul-check'))
        .filter(x => x.checked)
        .map(x => x.value);
    }

    async function refreshPreview() {
      if (!elHitung.checked) return;

      const masterId = elSekolah.value;
      const siswa = elSiswa.value || 0;
      const ids = getCheckedModulIds();

      preview.innerHTML = 'Menghitung...';
      try {
        const params = new URLSearchParams();
        params.set('master_sekolah_id', masterId);
        params.set('siswa', siswa);
        ids.forEach(id => params.append('modul_ids[]', id));

        const res = await fetch(`{{ route('tagihan.hitung') }}?` + params.toString());
        const json = await res.json();

        if (!json.ok) throw new Error('Gagal menghitung');

        // render preview
        if ((json.items || []).length === 0) {
          preview.innerHTML = '<span class="text-muted">Tidak ada modul untuk dihitung.</span>';
        } else {
          let html = '<div class="table-responsive"><table class="table table-sm mb-2"><thead><tr>' +
                     '<th>Modul</th><th class="text-end">Harga/siswa</th><th class="text-end">Siswa</th><th class="text-end">Subtotal</th>' +
                     '</tr></thead><tbody>';
          json.items.forEach(it => {
            html += `<tr><td>${it.nama}</td><td class="text-end">Rp ${Intl.NumberFormat('id-ID').format(it.harga)}</td>`+
                    `<td class="text-end">${it.siswa}</td>`+
                    `<td class="text-end">Rp ${Intl.NumberFormat('id-ID').format(it.subtotal)}</td></tr>`;
          });
          html += `</tbody><tfoot><tr><th colspan="3" class="text-end">Total</th>`+
                  `<th class="text-end">Rp ${Intl.NumberFormat('id-ID').format(json.total)}</th></tr></tfoot></table></div>`;
          preview.innerHTML = html;
        }

        // set total (readonly tapi tidak disabled supaya tetap terkirim)
        elTotal.value = json.total || 0;

      } catch (e) {
        preview.innerHTML = '<span class="text-danger">Gagal memuat perhitungan.</span>';
      }
    }

    function toggleAutoUI() {
      if (elHitung.checked) {
        areaAuto.style.display = '';
        elTotal.setAttribute('readonly', 'readonly');

        // set siswa default dari option sekolah bila kosong
        if (!elSiswa.value || elSiswa.value === '0') {
          const opt = elSekolah.selectedOptions[0];
          if (opt) elSiswa.value = opt.getAttribute('data-siswa') || 0;
        }

        // muat daftar modul terpasang SEBELUM hitung
        fetchAssigned();
      } else {
        areaAuto.style.display = 'none';
        elTotal.removeAttribute('readonly');
        preview.innerHTML = '<span class="text-muted">Aktifkan dan ubah parameter untuk melihat preview.</span>';
      }
    }

    // events
    elHitung.addEventListener('change', toggleAutoUI);

    elSekolah.addEventListener('change', () => {
      const opt = elSekolah.selectedOptions[0];
      if (opt && elHitung.checked) {
        elSiswa.value = opt.getAttribute('data-siswa') || 0;
        fetchAssigned();         // <— reload modul saat sekolah berubah
      }
    });

    // tetap ada: elSiswa + modul-check → refreshPreview()
    elSiswa && elSiswa.addEventListener('input', refreshPreview);
    document.addEventListener('change', (e) => {
      if (e.target && e.target.classList.contains('modul-check')) refreshPreview();
    });

    // init on load
    toggleAutoUI(); // kalau dari old('hitung_otomatis') === true, ini akan memanggil fetchAssigned()

    // Jadikan input Jatuh Tempo wajib:
    document.getElementById('formTagihan').addEventListener('submit', function(e){
      const jt = this.querySelector('[name="jatuh_tempo"]');
      if (!jt.value) {
        e.preventDefault();
        alert('Harap isi Jatuh Tempo.');
        jt.focus();
      }
    });
  </script>
@endsection
