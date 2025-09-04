@extends('layouts.app')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-2">
  <div>
    <div class="text-muted small">Laporan</div>
    <h5 class="mb-0">Laporan Eksekutif Tagihan</h5>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('tagihan.index') }}" class="btn btn-ghost btn-sm round">Kembali</a>
    <a href="{{ route('tagihan.laporan.csv', request()->query()) }}" class="btn btn-outline-success btn-sm round">
      <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
    </a>
  </div>
</div>

{{-- Toolbar filter (soft) --}}
<form method="GET" class="mb-3">
  <div class="toolbar">
    <div class="field" style="min-width:220px">
      <label>Klien</label>
      <select name="master_sekolah_id" class="select-soft">
        <option value="">— semua —</option>
        @foreach($klien as $k)
          <option value="{{ $k->id }}" {{ (string)request('master_sekolah_id')===(string)$k->id?'selected':'' }}>
            {{ $k->nama_sekolah }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="field" style="min-width:160px">
      <label>Status</label>
      <select name="status" class="select-soft">
        <option value="">— semua —</option>
        @foreach(['draft'=>'Draft','sebagian'=>'Sebagian','lunas'=>'Lunas'] as $val=>$label)
          <option value="{{ $val }}" {{ request('status')===$val?'selected':'' }}>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="field" style="min-width:160px">
      <label>Bulan</label>
      <input type="month" name="month" class="input-soft" value="{{ request('month') }}">
    </div>

    <div class="field">
      <label>Dari</label>
      <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-soft">
    </div>
    <div class="field">
      <label>Sampai</label>
      <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-soft">
    </div>

    <div class="field flex-grow-1">
      <label>Cari</label>
      <input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="nomor / keterangan">
    </div>

    <div class="field d-flex align-items-end" style="min-width:190px">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="due_only" name="due_only" value="1" {{ request('due_only')?'checked':'' }}>
        <label class="form-check-label ms-1" for="due_only">Hanya Due / Overdue</label>
      </div>
    </div>

    <div class="ms-auto d-flex align-items-end">
      <button class="btn btn-primary round">Terapkan</button>
    </div>
  </div>

  {{-- Quick range --}}
  <div class="d-flex flex-wrap gap-2 mt-2">
    <a href="{{ route('tagihan.laporan') }}" class="btn btn-ghost round">Reset</a>
    <a href="{{ route('tagihan.laporan', ['date_from'=>now()->startOfMonth()->toDateString(), 'date_to'=>now()->toDateString()]) }}" class="btn btn-ghost round">Bulan ini</a>
    <a href="{{ route('tagihan.laporan', ['date_from'=>now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'date_to'=>now()->subMonthNoOverflow()->endOfMonth()->toDateString()]) }}" class="btn btn-ghost round">Bulan lalu</a>
    <a href="{{ route('tagihan.laporan', ['date_from'=>now()->firstOfQuarter()->toDateString(), 'date_to'=>now()->toDateString()]) }}" class="btn btn-ghost round">Quarter</a>
    <a href="{{ route('tagihan.laporan', ['date_from'=>now()->startOfYear()->toDateString(), 'date_to'=>now()->toDateString()]) }}" class="btn btn-ghost round">YTD</a>
  </div>
</form>

{{-- Ringkasan --}}
<div class="row g-2 mb-3">
  <div class="col-6 col-md">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div class="text-muted small">Total Ditagihkan</div>
        <div class="fw-bold">{{ rupiah($summary['total'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div class="text-muted small">Terbayar</div>
        <div class="fw-bold">{{ rupiah($summary['terbayar'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div class="text-muted small">Sisa</div>
        <div class="fw-bold">{{ rupiah($summary['sisa'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div class="text-muted small">Overdue</div>
        <div class="fw-bold">{{ $summary['overdue_count'] ?? 0 }} tagihan</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div class="text-muted small">Collection Rate</div>
        <div class="fw-bold">{{ $summary['collection_rate'] ?? 0 }}%</div>
      </div>
    </div>
  </div>
</div>

{{-- Tabel --}}
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-modern table-hover align-middle mb-0">
      <thead class="table-light" style="position:sticky;top:0;z-index:1">
        <tr>
          <th>Sekolah</th>
          <th style="width:110px;">Nomor</th>
          <th style="width:120px;">Tgl Tagih</th>
          <th style="width:140px;">Jatuh Tempo</th>
          <th class="text-end" style="width:140px;">Total</th>
          <th class="text-end" style="width:140px;">Terbayar</th>
          <th class="text-end" style="width:140px;">Sisa</th>
          <th style="width:110px;">Aging</th>
          <th style="width:110px;">Kontak</th>
        </tr>
      </thead>
      <tbody>
        @php use Carbon\Carbon; @endphp
        @forelse($items as $t)
          @php
            $due   = $t->jatuh_tempo ? Carbon::parse($t->jatuh_tempo)->startOfDay() : null;
            $today = Carbon::today();
            $sisa  = max((int)$t->total - (int)$t->terbayar, 0);
            $over  = $due && $sisa > 0 && $due->lt($today);
            $days  = $over ? $due->diffInDays($today) : 0;
            $bucket = $over
              ? ($days <= 30 ? '0–30' : ($days <= 60 ? '31–60' : ($days <= 90 ? '61–90' : '>90')))
              : 'Current';
          @endphp

          <tr @class(['table-danger'=>$over])>
            <td>
              @if($t->sekolah)
                <a class="text-decoration-none" href="{{ route('master.aktivitas.index', $t->master_sekolah_id) }}">
                  {{ $t->sekolah->nama_sekolah }}
                </a>
              @else
                —
              @endif
            </td>

            <td>
              <a href="{{ route('tagihan.edit', $t->id) }}" class="text-decoration-none">{{ $t->nomor }}</a>
            </td>

            <td>{{ $t->tanggal_tagihan ? Carbon::parse($t->tanggal_tagihan)->format('d/m/Y') : '—' }}</td>
            <td>{{ $t->jatuh_tempo ? Carbon::parse($t->jatuh_tempo)->format('d/m/Y') : '—' }}</td>

            <td class="text-end">{{ rupiah($t->total) }}</td>
            <td class="text-end">{{ rupiah($t->terbayar) }}</td>
            <td class="fw-semibold text-end">{{ rupiah($sisa) }}</td>

            <td>
              <span class="badge {{ $over ? 'bg-danger' : 'bg-secondary' }}"
                    title="{{ $over ? ($days.' hari overdue') : 'Belum jatuh tempo' }}">
                {{ $bucket }}
              </span>
              @if($over)
                <div class="small text-muted">{{ $days }} hari</div>
              @endif
            </td>

            <td>
              @php
                $hp = $t->sekolah->no_hp ?? null;
                $wa = $hp ? preg_replace('/\D+/', '', $hp) : null;
              @endphp
              @if($wa)
                <a class="btn btn-success btn-sm round" target="_blank" href="https://wa.me/{{ $wa }}">WA</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-4">Tidak ada data.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Pagination --}}
<div class="mt-2">
  {{ $items->withQueryString()->links() }}
</div>

{{-- Tone lembut untuk baris overdue --}}
<style>
  .table-danger { background: #fff3f3 !important; }
</style>

@endsection
