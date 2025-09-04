@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Pembayaran Tagihan #{{ $tagihan->nomor ?? $tagihan->id }}</h4>
  <a href="{{ route('tagihan.show',$tagihan) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
</div>

@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
  <form method="post" action="{{ route('tagihan.bayar.simpan', $tagihan) }}" class="p-3">
    @csrf
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nominal</label>
        <input type="number" name="nominal" min="1" step="1" class="form-control" required>
        <div class="form-text">Sisa saat ini: {{ rupiah(max((int)$tagihan->total - (int)$tagihan->terbayar,0)) }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal Bayar</label>
        <input type="date" name="tanggal_bayar" class="form-control" value="{{ now()->format('Y-m-d') }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Metode</label>
        <input type="text" name="metode" class="form-control" placeholder="Transfer/Tunai/dll">
      </div>
      <div class="col-12">
        <label class="form-label">Catatan</label>
        <input type="text" name="catatan" class="form-control" placeholder="Opsional">
      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-primary">Simpan Pembayaran</button>
      <a href="{{ route('tagihan.show',$tagihan) }}" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>
@endsection
