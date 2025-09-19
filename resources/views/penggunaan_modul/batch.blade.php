@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Tambah Modul (Batch)</h5>
  <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
</div>

<form method="post" action="{{ route('penggunaan-modul.batch-store') }}" class="card p-3">
  @csrf
  <div class="mb-3">
    <label class="form-label">Sekolah</label>
    <input type="number" name="master_sekolah_id" class="form-control" value="{{ old('master_sekolah_id', $schoolId) }}" required>
    <div class="form-text">Isi ID Sekolah. (Jika kolommu bernama <code>klien_id</code>, sesuaikan di controller.)</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Pilih Modul</label>
    <div class="row">
      @foreach($modul as $m)
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="modul_ids[]" value="{{ $m->id }}" id="mod_{{ $m->id }}">
            <label class="form-check-label" for="mod_{{ $m->id }}">{{ $m->nama }}</label>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary">Tambahkan</button>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Batal</a>
  </div>
</form>
@endsection
