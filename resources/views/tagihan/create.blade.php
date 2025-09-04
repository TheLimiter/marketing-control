@extends('layouts.app')

@section('content')
@if($errors->any())

<div class="alert alert-danger">
<div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
<ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
<div>
<div class="text-muted small">{{ $title ?? 'Form' }}</div>
<h5 class="mb-0">{{ $subtitle ?? 'Buat Tagihan' }}</h5>
</div>
<a href="{{ $backRoute ?? url()->previous() }}" class="btn btn-ghost round">Kembali</a>
</div>

<form method="POST" action="{{ route('tagihan.store') }}">
@csrf
<div class="row g-3 form--soft">
<div class="col-lg-8">
<div class="card h-100">
<div class="card-body">
<div class="h6 mb-3">Informasi Utama</div>
<div class="row g-3">
<div class="col-md-12">
<label class="form-label">Sekolah</label>
<select name="master_sekolah_id" class="form-select" required>
@foreach($sekolah as $s)
<option value="{{ $s->id }}" {{ old('master_sekolah_id')==$s->id?'selected':'' }}>
{{ $s->nama_sekolah }}
</option>
@endforeach
</select>
@error('master_sekolah_id')<div class="text-danger small">{{ $message }}</div>@enderror
</div>

        <div class="col-md-6">
            <label class="form-label">Nomor Tagihan</label>
            <input name="nomor" class="form-control" value="{{ old('nomor') }}" required>
            @error('nomor')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Tanggal Tagihan</label>
            <input type="date" name="tanggal_tagihan" class="form-control"
                   value="{{ old('tanggal_tagihan', now()->toDateString()) }}">
            @error('tanggal_tagihan')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Total</label>
            <input type="number" name="total" class="form-control" value="{{ old('total',0) }}" min="0" required>
            @error('total')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Terbayar (opsional)</label>
            <input type="number" name="terbayar" class="form-control" value="{{ old('terbayar',0) }}" min="0">
            @error('terbayar')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
            @error('catatan')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
  </div>
</div>

</div>
<div class="col-lg-4">
<div class="card h-100">
<div class="card-body">
<div class="h6 mb-3">Opsi</div>
<div class="row g-3">
<div class="col-12">
<label class="form-label">Jatuh Tempo</label>
<input type="date" name="jatuh_tempo" class="form-control" value="{{ old('jatuh_tempo') }}">
@error('jatuh_tempo')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
<div class="col-12">
<label class="form-label">Status</label>
<select name="status" class="form-select">
@foreach(['draft','terkirim','lunas'] as $st)
<option value="{{ $st }}" {{ old('status','draft')===$st?'selected':'' }}>{{ ucfirst($st) }}</option>
@endforeach
</select>
@error('status')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
</div>
</div>
</div>
</div>
</div>

<div class="position-sticky bottom-0 mt-3" style="z-index:10;">
<div class="card p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
<div class="text-muted small">Periksa lagi sebelum menyimpan.</div>
<div class="d-flex gap-2">
<a href="{{ route('tagihan.index') }}" class="btn btn-ghost round">Batal</a>
<button class="btn btn-primary round">Simpan</button>
</div>
</div>
</div>
</form>
@endsection
