@extends('layouts.app')

@section('content')
@if($errors->any())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="text-muted small">Modul</div>
    <h5 class="mb-0">Edit Modul: {{ $modul->nama }}</h5>
  </div>
  <a href="{{ route('modul.index') }}" class="btn btn-ghost round">Kembali</a>
</div>

<form method="POST" action="{{ route('modul.update', $modul) }}">
  @csrf @method('PUT')

  <div class="row g-3 form--soft">
    {{-- Kartu 1: Informasi Modul --}}
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Informasi Modul</div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Kode</label>
              <input class="form-control input-soft" name="kode" value="{{ old('kode', $modul->kode) }}" placeholder="mis. M-001">
            </div>
            <div class="col-md-5">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input class="form-control input-soft" name="nama" value="{{ old('nama', $modul->nama) }}" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Kategori</label>
              <input class="form-control input-soft" name="kategori" value="{{ old('kategori', $modul->kategori) }}">
            </div>
            <div class="col-md-2">
              <label class="form-label">Versi</label>
              <input class="form-control input-soft" name="versi" value="{{ old('versi', $modul->versi) }}">
            </div>

            <div class="col-12">
              <label class="form-label d-flex justify-content-between">
                <span>Deskripsi</span>
                <small class="text-muted"><span id="desc-count">0</span> karakter</small>
              </label>
              <textarea class="form-control input-soft" name="deskripsi" id="deskripsi" rows="3">{{ old('deskripsi', $modul->deskripsi) }}</textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Kartu 2: Harga & Status --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="h6 mb-3">Harga & Status</div>

          <div class="mb-3">
            <label class="form-label">Harga Default</label>
            <input class="form-control input-soft" name="harga_default" type="number" min="0" value="{{ old('harga_default', $modul->harga_default) }}">
          </div>

          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="aktif" name="aktif" value="1" @checked(old('aktif', (int)$modul->aktif)==1)>
            <label for="aktif" class="form-check-label">Aktif</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Sticky Action Bar --}}
  <div class="position-sticky bottom-0 mt-3" style="z-index:10;">
    <div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <div class="text-muted small">Terakhir diubah: {{ optional($modul->updated_at)->format('d/m/Y H:i') }}</div>
      <div class="d-flex gap-2">
        <a href="{{ route('modul.index') }}" class="btn btn-ghost round">Batal</a>
        <button class="btn btn-primary round">Simpan</button>
      </div>
    </div>
  </div>
</form>

{{-- kecil: hitung karakter deskripsi --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ta = document.getElementById('deskripsi');
  const cnt = document.getElementById('desc-count');
  if (!ta || !cnt) return;
  const update = () => cnt.textContent = (ta.value || '').length;
  ta.addEventListener('input', update); update();
});
</script>
@endsection
