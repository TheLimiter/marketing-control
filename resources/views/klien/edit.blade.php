@extends('layouts.app')
@section('content')
<h4 class="mb-3">Edit Klien: {{ $klien->nama }}</h4>
<div class="card p-3" style="max-width:720px">
    {{-- Menampilkan pesan 'info' dari konversi prospek --}}
    @if(session('info'))
      <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    {{-- Menampilkan pesan 'ok' dari proses update form --}}
    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form action="{{ route('klien.update', $klien->id) }}" method="post" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nama Klien</label>
            <input type="text" name="nama" class="form-control" value="{{ old('nama', $klien->nama) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tanggal MOU</label>
            <input type="date" name="tanggal_mou" class="form-control" value="{{ old('tanggal_mou', $klien->tanggal_mou) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">File MOU (PDF)</label>
            <input type="file" name="mou_file" class="form-control">
            @if($klien->mou_file)
                <small class="text-muted d-block mt-1">File tersimpan: <a href="{{ Storage::url($klien->mou_file) }}" target="_blank">Download</a></small>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label">Status TTD</label>
            <select name="status_ttd" class="form-select">
                <option value="belum" {{ old('status_ttd', $klien->status_ttd) == 'belum' ? 'selected' : '' }}>Belum</option>
                <option value="sudah" {{ old('status_ttd', $klien->status_ttd) == 'sudah' ? 'selected' : '' }}>Sudah</option>
            </select>
        </div>

        {{-- ... input fields lainnya sesuai kebutuhan ... --}}

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('klien.index') }}" class="btn btn-light">Kembali</a>
        </div>
    </form>
</div>
@endsection
