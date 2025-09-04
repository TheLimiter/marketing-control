@php
  // dipakai di edit (punya $master) & create (nggak punya)
  $row = $row ?? ($master ?? null);
@endphp

{{-- Error summary (opsional, tampil kalau ada error) --}}
@if ($errors->any())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
    <ul class="mb-0">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3 form--soft">
  {{-- KIRI: Identitas & Kontak --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="h6 mb-0">Identitas & Kontak</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
          <input type="text" name="nama_sekolah" class="form-control" autofocus
                 placeholder="Masukkan nama sekolah"
                 value="{{ old('nama_sekolah', $row->nama_sekolah ?? '') }}" required>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Jenjang</label>
            <input type="text" name="jenjang" class="form-control"
                   placeholder="SMK / SMA / SMP / SD"
                   value="{{ old('jenjang', $row->jenjang ?? '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Jumlah Siswa</label>
            <input type="number" name="jumlah_siswa" class="form-control"
                   min="0" step="10" inputmode="numeric"
                   placeholder="Contoh: 650"
                   value="{{ old('jumlah_siswa', $row->jumlah_siswa ?? '') }}">
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Narahubung</label>
            <input type="text" name="narahubung" class="form-control"
                   placeholder="Nama, Jabatan"
                   value="{{ old('narahubung', $row->narahubung ?? '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input type="text" name="no_hp" class="form-control" inputmode="tel"
                   placeholder="08xxxxxxx"
                   value="{{ old('no_hp', $row->no_hp ?? '') }}">
            <div class="form-text">Gunakan format lokal (contoh: 08xxxxxxxxxx).</div>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Sumber</label>
          <input type="text" name="sumber" class="form-control"
                 placeholder="Kerjasama / event / kontak / dsb."
                 value="{{ old('sumber', $row->sumber ?? '') }}">
        </div>
      </div>
    </div>
  </div>

  {{-- KANAN: Alamat & Catatan --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="h6 mb-0">Alamat & Catatan</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" rows="3" class="form-control" placeholder="Nama jalan, kecamatan, kota/kabupaten…">{{ old('alamat', $row->alamat ?? '') }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Tindak Lanjut</label>
          <textarea name="tindak_lanjut" rows="2" class="form-control" placeholder="Rencana follow-up berikutnya…">{{ old('tindak_lanjut', $row->tindak_lanjut ?? '') }}</textarea>
          <div class="form-text mt-1">Rencana ke depan atau follow-up yang perlu dilakukan terhadap sekolah.</div>
        </div>

        <div class="mb-1">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Catatan</span>
            <small class="text-muted"><span id="catatan-count">0</span> karakter</small>
          </label>
          <textarea id="catatan" name="catatan" rows="5" class="form-control" placeholder="Ringkasan call/visit, komitmen, hal khusus…">{{ old('catatan', $row->catatan ?? '') }}</textarea>
        </div>

        <div class="text-muted small">
          Gunakan catatan untuk ringkasan call/visit, komitmen, atau hal khusus.
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Sticky action bar --}}
<div class="position-sticky bottom-0 mt-3" style="z-index:10;">
  <div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <div class="text-muted small">
      @if(isset($row->updated_at))
        Terakhir diperbarui: {{ optional($row->updated_at)->diffForHumans() }}
      @endif
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('master.index') }}" class="btn btn-ghost round">Batal</a>
      <button type="submit" class="btn btn-primary round">Simpan</button>
    </div>
  </div>
</div>

{{-- kecil: hitung karakter catatan --}}
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ta = document.getElementById('catatan');
    const cnt = document.getElementById('catatan-count');
    if (!ta || !cnt) return;
    const update = () => { cnt.textContent = (ta.value || '').length; };
    ta.addEventListener('input', update);
    update();
  });
</script>
