@extends('layouts.app')
@section('content')
<h4 class="mb-3">Daftar Notifikasi</h4>
<div class="card"><div class="table-responsive">
<table class="table table-hover m-0">
  <thead><tr><th>Waktu</th><th>Klien</th><th>Tagihan</th><th>Saluran</th><th>Status</th></tr></thead>
  <tbody>
  @forelse($items as $n)
    <tr>
      <td>{{ $n->created_at->format('d/m/Y H:i') }}</td>
      <td>{{ $n->tagihan->klien->nama_sekolah ?? '-' }}</td>
      <td>
        {{ $n->tagihan->nomor ?? '-' }}
        @if($n->tagihan?->jatuh_tempo)
          <div class="text-muted small">Jatuh tempo: {{ $n->tagihan->jatuh_tempo }}</div>
        @endif
      </td>
      <td>{{ $n->saluran }}</td>
      <td>{{ $n->status }}</td>
    </tr>
  @empty
    <tr><td colspan="5" class="text-center text-muted">Belum ada notifikasi</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
