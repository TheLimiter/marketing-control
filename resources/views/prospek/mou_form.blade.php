@extends('layouts.app')
@section('content')
<h5>MOU & TTD â€” {{ $master->nama_sekolah }}</h5>
@if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<form action="{{ route('master.mou.save', $master->id) }}" method="post" enctype="multipart/form-data" class="mt-3">
  @csrf
  <div class="mb-2">
    <label class="form-label">Upload MOU (PDF/JPG/PNG)</label>
    <input type="file" name="mou" class="form-control">
    @if($doc && $doc->mou_path)
      <small>File saat ini: <a target="_blank" href="{{ asset('storage/'.$doc->mou_path) }}">lihat</a></small>
    @endif
  </div>

  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="ttd_status" name="ttd_status" value="1" {{ ($doc && $doc->ttd_status) ? 'checked' : '' }}>
    <label class="form-check-label" for="ttd_status">Sudah ditandatangani</label>
  </div>

  <div class="mb-3">
    <label class="form-label">Catatan</label>
    <textarea class="form-control" name="catatan" rows="2">{{ old('catatan', $doc->catatan ?? '') }}</textarea>
  </div>

  <button class="btn btn-primary">Simpan</button>
</form>
@endsection
