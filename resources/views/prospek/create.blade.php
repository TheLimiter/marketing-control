@extends('layouts.app')
@section('content')
<form action="{{ route('prospek.store') }}" method="post" class="card p-3" style="max-width:680px">@csrf
  <h5 class="mb-3">Tambah Aktivitas</h5>
  <div class="mb-3"><label class="form-label">Calon Klien</label>
    <select name="calon_klien_id" class="form-select" required>
      @foreach($calon as $c)
        <option value="{{ $c->id }}">{{ $c->nama }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Jenis</label>
    <select name="jenis" class="form-select" required>
      <option>Undangan</option><option>Proposal</option><option>Kunjungan</option><option>Webinar</option><option>Call</option>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Hasil</label>
    <select name="hasil" class="form-select" required>
      <option>Follow Up</option><option>Positif</option><option>Negatif</option>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Catatan</label><textarea name="catatan" class="form-control"></textarea></div>
  <button class="btn btn-primary">Simpan</button>
</form>
@endsection
