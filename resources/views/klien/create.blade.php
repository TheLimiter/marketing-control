@extends('layouts.app')
@section('content')
<h4 class="mb-3">Tambah Klien</h4>
<form action="{{ route('klien.store') }}" method="post" enctype="multipart/form-data" class="card p-3" style="max-width:720px">
  @csrf
  <div class="mb-3">
    <label class="form-label">Nama</label>
    <input name="nama" class="form-control" required value="{{ old('nama') }}">
  </div>
  <div class="mb-3">
    <label class="form-label">Tanggal MOU</label>
    <input type="date" name="tanggal_mou" class="form-control" value="{{ old('tanggal_mou') }}">
  </div>
  <div class="mb-3">
    <label class="form-label">File MOU (PDF)</label>
    <input type="file" name="mou_file" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Status TTD</label>
    <select name="status_ttd" class="form-select">
      <option value="belum" selected>Belum</option>
      <option value="sudah">Sudah</option>
    </select>
  </div>
  <button class="btn btn-primary">Simpan</button>
  <a href="{{ route('klien.index') }}" class="btn btn-light">Kembali</a>
</form>
@endsection
