@extends('layouts.app')

@section('content')
<h4 class="mb-3">MOU Prospek: {{ $prospek->calon->nama ?? 'â€”' }} (#{{ $prospek->id }})</h4>

@if(session('ok'))    <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

<form action="{{ route('prospek.mou.upload', $prospek->id) }}" method="post" enctype="multipart/form-data" class="card p-3" style="max-width:720px">
  @csrf
  <div class="mb-3">
    <label class="form-label">Tanggal MOU</label>
    <input type="date" name="tanggal_mou" class="form-control"
           value="{{ old('tanggal_mou', $prospek->mou_at ?? $prospek->tanggal_mou) }}">
  </div>

  <div class="mb-3">
    <label class="form-label">File MOU (PDF)</label>
    <input type="file" name="mou_file" class="form-control">
    @php $mou = $prospek->mou_file ?? $prospek->mou_path ?? null; @endphp
    @if($mou)
      <small class="text-muted d-block mt-1">
        File tersimpan. <a href="{{ asset('storage/'.$mou) }}" target="_blank" rel="noopener">Unduh</a>
      </small>
    @endif
  </div>

  <div class="mb-3">
    <div>Status TTD:
      <strong>{{ ($prospek->ttd_status === 'sudah' || $prospek->ttd_status == 1) ? 'Sudah' : 'Belum' }}</strong>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary"  name="action" value="save">Simpan MOU</button>

    @if(!($prospek->ttd_status === 'sudah' || $prospek->ttd_status == 1))
      <button class="btn btn-success" name="action" value="ttd">Sudah TTD</button>
    @else
      <button class="btn btn-warning" name="action" value="unttd"
              onclick="return confirm('Batalkan status TTD?')">Batalkan TTD</button>
    @endif

    <a href="{{ url()->previous() }}" class="btn btn-light">Kembali</a>
  </div>
</form>
@endsection
