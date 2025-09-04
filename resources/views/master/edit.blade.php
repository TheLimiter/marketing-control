@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0">Edit Sekolah</h5>
    <div class="text-muted small">{{ $master->nama_sekolah }}</div>
  </div>
  <div class="page-toolbar d-flex gap-2">
    <a href="{{ route('master.show', $master->id) }}" class="btn btn-sm btn-outline-secondary">Detail</a>

    {{-- Hapus (ke Sampah) --}}
    <form action="{{ route('master.destroy', $master->id) }}" method="post"
          onsubmit="return confirm('Pindahkan data ke Sampah?')">
      @csrf @method('DELETE')
      <button class="btn btn-sm btn-outline-danger">Hapus</button>
    </form>

    <a href="{{ route('master.aktivitas.index', $master->id) }}" class="btn btn-sm btn-outline-secondary">
  Aktivitas
</a>

    <a href="{{ route('master.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
  </div>
</div>

<form action="{{ route('master.update', $master->id) }}" method="post" class="mb-4">
  @csrf @method('PUT')
  @include('master.form', ['row' => $master])
</form>
@endsection
