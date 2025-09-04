@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Detail Calon Klien</h4>
  <div class="d-flex gap-2">
    <a href="{{ route('calon.edit',$calon) }}" class="btn btn-warning">Edit</a>
    <form action="{{ route('calon.destroy',$calon) }}" method="post" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')
      <button class="btn btn-danger">Hapus</button>
    </form>
    <a href="{{ route('calon.index') }}" class="btn btn-light">Kembali</a>
  </div>
</div>
<div class="card"><div class="card-body">
  <dl class="row mb-0">
    <dt class="col-sm-3">Nama</dt><dd class="col-sm-9">{{ $calon->nama }}</dd>
    <dt class="col-sm-3">Narahubung</dt><dd class="col-sm-9">{{ $calon->narahubung }}</dd>
    <dt class="col-sm-3">No HP</dt><dd class="col-sm-9">{{ $calon->no_hp }}</dd>
    <dt class="col-sm-3">Jenjang</dt><dd class="col-sm-9">{{ $calon->jenjang }}</dd>
    <dt class="col-sm-3">Alamat</dt><dd class="col-sm-9">{{ $calon->alamat }}</dd>
    <dt class="col-sm-3">Sumber</dt><dd class="col-sm-9">{{ $calon->sumber }}</dd>
    <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">{{ $calon->catatan }}</dd>
  </dl>
</div></div>
@endsection
