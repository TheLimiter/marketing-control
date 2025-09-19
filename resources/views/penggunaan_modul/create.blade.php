@extends('layouts.app')

@section('content')
@if($errors->any())
  <div class="alert alert-danger elev-1">
    <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="text-muted small">Penggunaan Modul</div>
    <h5 class="mb-0">Tambah Penggunaan Modul</h5>
  </div>
  <a href="{{ route('penggunaan-modul.index') }}" class="btn btn-ghost round">Kembali</a>
</div>

<form method="POST" action="{{ route('penggunaan-modul.store') }}">
  @csrf

  <div class="row g-3">
    {{-- Kartu 1: Penugasan --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Penugasan</div>

          <div class="mb-3">
            <label class="form-label">Sekolah <span class="text-danger">*</span></label>
            <select class="form-select select-soft" name="master_sekolah_id" required>
              <option value="">Pilih sekolah</option>
              @foreach($sekolah as $s)
                <option value="{{ $s->id }}" @selected(old('master_sekolah_id')==$s->id)>{{ $s->nama_sekolah }}</option>
              @endforeach
            </select>
            <div class="form-text">Saat pilih sekolah, data terakhir (PIC/kontak/lisensi) akan terisi otomatis.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Modul <span class="text-danger">*</span></label>
            <select class="form-select select-soft" name="modul_id" required>
              <option value="">Pilih modul</option>
              @foreach($modul as $m)
                <option value="{{ $m->id }}" @selected(old('modul_id')==$m->id)>{{ $m->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Pengguna Modul</label>
              <input class="form-control input-soft" name="pengguna_nama" placeholder="Nama PIC/Narahubung" value="{{ old('pengguna_nama') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Kontak</label>
              <input class="form-control input-soft" name="pengguna_kontak" placeholder="Telp/Email" value="{{ old('pengguna_kontak') }}">
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Kartu 2: Periode & Opsi --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Periode & Opsi</div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Mulai</label>
              <input type="date" class="form-control input-soft" name="mulai_tanggal" id="mulai_tanggal"
                     value="{{ old('mulai_tanggal', now()->toDateString()) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Akhir</label>
              <input type="date" class="form-control input-soft" name="akhir_tanggal" id="akhir_tanggal"
                     value="{{ old('akhir_tanggal') }}">
              <div class="form-text">Kosongkan jika ongoing.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Atau Durasi (hari)</label>
              <input type="number" class="form-control input-soft" name="durasi_hari" id="durasi_hari"
                     min="1" max="365" placeholder="mis. 14" value="{{ old('durasi_hari') }}">
              <div class="form-text">Otomatis hitung tanggal akhir (inklusif).</div>
            </div>
          </div>

          <div class="row g-3 mt-0">
            <div class="col-md-12 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="is_official" name="is_official" value="1" @checked(old('is_official')==1)>
                <label for="is_official" class="form-check-label">Official (sudah bayar)</label>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Catatan</label>
            <textarea class="form-control input-soft" name="catatan" rows="3" placeholder="Keterangan tambahan">{{ old('catatan') }}</textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Sticky Action Bar --}}
  <div class="position-sticky bottom-0 mt-3" style="z-index:10;">
    <div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <div class="text-muted small">Isi data sesuai kebutuhan modul</div>
      <div class="d-flex gap-2">
        <a href="{{ route('penggunaan-modul.index') }}" class="btn btn-ghost round">Batal</a>
        <button class="btn btn-primary round">Simpan</button>
      </div>
    </div>
  </div>
</form>

{{-- Durasi tanggal akhir (inklusif) + guard --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const dur = document.getElementById('durasi_hari');
  const s   = document.getElementById('mulai_tanggal');
  const e   = document.getElementById('akhir_tanggal');

  const toISO = (d)=> d.toISOString().slice(0,10);

  const fillEnd = () => {
    const n = parseInt(dur.value, 10);
    if (!n || n <= 0) return;
    const start = s.value ? new Date(s.value) : new Date();
    const end   = new Date(start);
    end.setDate(end.getDate() + (n - 1)); // inklusif: 1 hari = start==end
    e.value = toISO(end);
    if (s.value) e.min = s.value;
  };

  const clampEnd = () => {
    if (!s.value || !e.value) return;
    if (new Date(e.value) < new Date(s.value)) e.value = s.value;
    e.min = s.value;
  };

  if (dur) dur.addEventListener('input', fillEnd);
  if (s)   s.addEventListener('change', () => { clampEnd(); if (dur.value) fillEnd(); });
  clampEnd();
});
</script>

{{-- Prefill dari riwayat sekolah --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const selSek  = document.querySelector('select[name="master_sekolah_id"]');
  const nama    = document.querySelector('input[name="pengguna_nama"]');
  const kontak  = document.querySelector('input[name="pengguna_kontak"]');
  const official= document.getElementById('is_official');
  const startEl = document.getElementById('mulai_tanggal');

  async function doPrefill(id){
    if(!id) return;
    try{
      const res  = await fetch(`{{ route('penggunaan-modul.prefill') }}?master_id=${id}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const json = await res.json();
      if(json.ok){
        if(nama   && !nama.value)   nama.value   = json.data.pengguna_nama   || '';
        if(kontak && !kontak.value) kontak.value = json.data.pengguna_kontak || '';
        if(official) official.checked = !!json.data.is_official;
        // start default sudah diisi server dengan today; biarkan kecuali kosong
        if(startEl && !startEl.value) startEl.value = json.data.mulai_tanggal || '';
      }
    }catch(e){ console.error(e); }
  }

  if(selSek){
    selSek.addEventListener('change', e => doPrefill(e.target.value));
    if(selSek.value) doPrefill(selSek.value);
  }
});
</script>
@endsection
