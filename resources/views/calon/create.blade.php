@extends('layouts.app')
@section('content')
<form action="{{ route('calon.store') }}" method="post" class="card p-3" style="max-width:680px">@csrf
  <h5 class="mb-3">Tambah Calon Klien</h5>
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Narahubung</label><input name="narahubung" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">No HP</label><input name="no_hp" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Jenjang</label><input name="jenjang" class="form-control" placeholder="SD/SMP/SMA/SMK"></div>
    <div class="col-12"><label class="form-label">Alamat</label><input name="alamat" class="form-control"></div>
    <div class="col-12"><label class="form-label">Sumber</label><input name="sumber" class="form-control"></div>
    <div class="col-12"><label class="form-label">Catatan</label><textarea name="catatan" class="form-control"></textarea></div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Simpan</button><a href="{{ route('calon.index') }}" class="btn btn-light">Batal</a></div>
</form>
@endsection
