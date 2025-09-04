@extends('layouts.app')
@section('content')
<form action="{{ route('calon.update',$calon) }}" method="post" class="card p-3" style="max-width:680px">@csrf @method('PUT')
  <h5 class="mb-3">Edit Calon Klien</h5>
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label">Nama</label><input name="nama" class="form-control" value="{{ old('nama',$calon->nama) }}" required></div>
    <div class="col-md-4"><label class="form-label">Narahubung</label><input name="narahubung" class="form-control" value="{{ old('narahubung',$calon->narahubung) }}"></div>
    <div class="col-md-6"><label class="form-label">No HP</label><input name="no_hp" class="form-control" value="{{ old('no_hp',$calon->no_hp) }}"></div>
    <div class="col-md-6"><label class="form-label">Jenjang</label><input name="jenjang" class="form-control" value="{{ old('jenjang',$calon->jenjang) }}" placeholder="SD/SMP/SMA/SMK"></div>
    <div class="col-12"><label class="form-label">Alamat</label><input name="alamat" class="form-control" value="{{ old('alamat',$calon->alamat) }}"></div>
    <div class="col-12"><label class="form-label">Sumber</label><input name="sumber" class="form-control" value="{{ old('sumber',$calon->sumber) }}"></div>
    <div class="col-12"><label class="form-label">Catatan</label><textarea name="catatan" class="form-control">{{ old('catatan',$calon->catatan) }}</textarea></div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary">Simpan Perubahan</button>
    <a href="{{ route('calon.index') }}" class="btn btn-light">Batal</a>
  </div>
</form>
@endsection
