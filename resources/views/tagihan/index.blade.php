@extends('layouts.app')

@php
$lapParams = array_filter([
'master_sekolah_id' => request('master_sekolah_id'),
'status' => request('status'),
'date_from' => request('dari'),
'date_to' => request('sampai'),
'q' => request('q'),
// laporan pakai due_only; kalau mau ikutkan centang "Due (hari ini)" dari index:
'due_only' => request('only_due') ? 1 : null,
], fn($v) => $v !== null && $v !== '');

$toggle = request('dir','desc')==='desc' ? 'asc' : 'desc';
@endphp

@section('content')

<div class="d-flex justify-content-between align-items-center mb-2">
<div>
<div class="text-muted small">Data</div>
<h5 class="mb-0">Tagihan Klien</h5>
</div>
<div class="d-flex gap-2">
<a href="{{ route('tagihan.create') }}" class="btn btn-primary btn-sm round">
<i class="bi bi-plus-lg me-1"></i> Buat Tagihan
</a>
<a href="{{ route('tagihan.laporan', $lapParams) }}" class="btn btn-success btn-sm round">Laporan</a>
<a href="{{ route('tagihan.laporan.csv', $lapParams) }}" class="btn btn-outline-success btn-sm round">Export CSV</a>
<a href="{{ route('tagihan.notifikasi.hminus', ['hari'=>30]) }}" class="btn btn-outline-secondary btn-sm round">Notifikasi H-30</a>
</div>
</div>

<form method="get" class="mb-3">
<div class="toolbar">
<div class="field">
<label class="form-label">Klien</label>
<select name="master_sekolah_id" class="select-soft">
<option value="">— semua —</option>
@foreach($sekolah as $s)
<option value="{{ $s->id }}" {{ (string)(request('master_sekolah_id') ?? '')===(string)$s->id ? 'selected' : '' }}>
{{ $s->nama_sekolah }}
</option>
@endforeach
</select>
</div>
<div class="field">
<label class="form-label">Status</label>
<select name="status" class="select-soft">
<option value="">— semua —</option>
@foreach(['lunas','sebagian','draft'] as $s)
<option value="{{ $s }}" {{ (request('status') ?? '')===$s ? 'selected' : '' }}>{{ ucwords($s) }}</option>
@endforeach
</select>
</div>
<div class="field">
<label>Dari</label>
<input type="date" name="dari" value="{{ request('dari') }}" class="input-soft">
</div>
<div class="field">
<label>Sampai</label>
<input type="date" name="sampai" value="{{ request('sampai') }}" class="input-soft">
</div>
<div class="field flex-grow-1" style="min-width:220px">
<label>Cari</label>
<input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="keterangan">
</div>
<div class="field" style="min-width:140px">
<label>Per</label>
<select name="per" class="select-soft" onchange="this.form.submit()">
@foreach([15,25,50,100] as $n)
<option value="{{ $n }}" @selected(request('per',25)==$n)>{{ $n }}</option>
@endforeach
</select>
</div>
<div class="ms-auto d-flex align-items-end">
<button class="btn btn-primary round">Terapkan</button>
</div>
</div>

<div class="d-flex flex-wrap gap-2 mt-2">
<a href="{{ url()->current() }}" class="btn btn-ghost round">Reset</a>
<a href="{{ request()->fullUrlWithQuery(['dari'=>now()->toDateString(),'sampai'=>now()->toDateString()]) }}" class="btn btn-ghost round">Hari ini</a>
<a href="{{ request()->fullUrlWithQuery(['dari'=>now()->subDays(7)->toDateString(),'sampai'=>now()->toDateString()]) }}" class="btn btn-ghost round">7 hari</a>

<div class="form-check d-flex align-items-center me-2">
  <input type="checkbox" class="form-check-input" id="only_due" name="only_due"
    {{ request('only_due') ? 'checked' : '' }}>
  <label class="form-check-label ms-2" for="only_due">Due (hari ini)</label>
</div>

<div class="form-check d-flex align-items-center">
  <input type="checkbox" class="form-check-input" id="only_overdue" name="only_overdue"
    {{ request('only_overdue') ? 'checked' : '' }}>
  <label class="form-check-label ms-2" for="only_overdue">Overdue</label>
</div>

</div>
</form>

<div class="card">
<div class="table-responsive">
@if(isset($summary))
<div class="d-flex flex-wrap gap-2 m-3">
<span class="badge bg-secondary">Total: {{ rupiah($summary['total']) }}</span>
<span class="badge bg-success">Terbayar: {{ rupiah($summary['terbayar']) }}</span>
<span class="badge bg-warning text-dark">Sisa: {{ rupiah($summary['sisa']) }}</span>
<span class="badge bg-primary">Due: {{ $summary['due'] }}</span>
<span class="badge bg-danger">Overdue: {{ $summary['overdue'] }}</span>
<span class="badge bg-info text-dark">CR: {{ $summary['cr'] }}%</span>
</div>
@endif
<table class="table table-modern table-hover align-middle mb-0">
<thead class="table-light">
<tr>
<th>Klien</th>
<th>Nomor</th>
<th>Tgl Tagih</th>
<th>Jatuh Tempo</th>
<th>Jumlah</th>
<th>Bayar</th>
<th>Sisa</th>
<th>Status</th>
<th class="text-end">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($items as $t)
@php
$isDue = $t->jatuh_tempo && $t->jatuh_tempo === now()->toDateString() && $t->terbayar < $t->total;
$isOverdue = $t->jatuh_tempo && $t->jatuh_tempo < now()->toDateString() && $t->terbayar < $t->total;
@endphp
<tr class="{{ $isOverdue ? 'table-danger' : ($isDue ? 'table-warning' : '') }}">
<td>{{ $t->sekolah->nama_sekolah ?? '-' }}</td>
<td>
<a href="{{ route('tagihan.show', $t) }}" class="text-decoration-none">
{{ $t->nomor }}
</a>
</td>
<td>{{ optional($t->tanggal_tagihan)->format('d/m/Y') }}</td>
<td>
@if($t->jatuh_tempo)
<span class="badge bg-{{ $t->due_badge ?? 'secondary' }}">
{{ \Carbon\Carbon::parse($t->jatuh_tempo)->format('d/m/Y') }}
</span>
@if($t->is_overdue)
<span class="badge bg-danger">Overdue</span>
@endif
@else
<span class="text-muted">-</span>
@endif
</td>
<td>{{ rupiah($t->total) }}</td>
<td>{{ rupiah($t->terbayar) }}</td>
<td>{{ rupiah($t->sisa) }}</td>
<td>
<span class="badge bg-{{ $t->status === 'lunas' ? 'success' : 'secondary' }}">
{{ ucwords($t->status) }}
</span>
</td>
<td class="text-end">
<div class="d-inline-flex flex-wrap gap-1 justify-content-end">
<a href="{{ route('tagihan.show',$t) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
<a href="{{ route('tagihan.edit',$t) }}" class="btn btn-sm btn-warning">Edit</a>
@if($t->wa_url)
<a href="{{ route('tagihan.wa', $t->id) }}" target="_blank" class="btn btn-sm btn-success">WA</a>
@endif
<a href="{{ route('tagihan.notifikasi', $t->id) }}" class="btn btn-sm btn-outline-secondary">Notif</a>
<form action="{{ route('tagihan.destroy',$t) }}" method="post" class="d-inline"
onsubmit="return confirm('Hapus tagihan ini?')">
@csrf @method('DELETE')
<button class="btn btn-sm btn-danger">Hapus</button>
</form>
</div>
</td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-muted">Belum ada data</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
<div class="small text-muted">
@if(method_exists($items ?? null,'firstItem'))
Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} data
@endif
</div>
{{ ($items ?? null)?->appends(request()->query())->links() }}
</div>
</div>
@endsection
