@extends('layouts.app')

@section('content')
<h4 class="mb-3">MOU Calon Klien: {{ $calon->nama ?? '' }} (#{{ $calon->id }})</h4>

<form action="{{ route('calon.mou.update', $calon) }}" method="post" enctype="multipart/form-data">
  @csrf
  <div class="mb-3">
    <label class="form-label">Tanggal MOU</label>
    <input type="date" name="tanggal_mou" class="form-control" value="{{ old('tanggal_mou', $calon->tanggal_mou) }}">
  </div>

  <div class="mb-3">
    <label class="form-label">File MOU (PDF)</label>
    <input type="file" name="mou_file" class="form-control" {{ $calon->mou_file ? '' : 'required' }}>
    @if($calon->mou_file)
      <small class="text-muted d-block mt-1">
        File tersimpan. <a href="{{ asset('storage/' . $calon->mou_file) }}" target="_blank" rel="noopener">Unduh</a>
      </small>
    @endif
  </div>

  <button class="btn btn-primary">Simpan</button>
  <a href="{{ url()->previous() }}" class="btn btn-light">Kembali</a>
</form>
@endsection
