@extends('layouts.app')

@php
use Carbon\Carbon;
use Illuminate\Support\Str;

// Normalisasi angka
$total   = (int) ($total   ?? 0);
$selesai = (int) ($selesai ?? 0);
$aktif   = (int) ($aktif   ?? 0);
$percent = (int) ($percent ?? 0);

// Ambil lastUpdate aman (bisa Carbon|string|null|Optional)
$last = $lastUpdate ?? null;
if ($last instanceof \Illuminate\Support\Optional) {
    $last = $last->__toString() ?: null;
}
if ($last instanceof \DateTimeInterface) {
    $lastExact = $last->format('d/m/Y H:i');
    $lastHuman = $last->diffForHumans();
} elseif (is_string($last) && trim($last) !== '') {
    try {
        $c         = Carbon::parse($last);
        $lastExact = $c->format('d/m/Y H:i');
        $lastHuman = $c->diffForHumans();
    } catch (\Throwable $e) {
        $lastExact = '—';
        $lastHuman = null;
    }
} else {
    $lastExact = '—';
    $lastHuman = null;
}

// Kelas warna progress
$barClass = $percent >= 100 ? 'bg-success'
          : ($percent >= 75 ? 'bg-primary'
          : ($percent >= 40 ? 'bg-info' : 'bg-warning'));

@endphp

@section('content')
{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
<div>
{{-- Judul: Pastikan menggunakan $master->nama_sekolah --}}
<div class="h-page">Progress Modul — {{ $master->nama_sekolah }}</div>
<div class="subtle">Ringkasan & Detail</div>
</div>
<div class="d-flex gap-2">
<a href="{{ url()->previous() }}" class="btn btn-ghost round">
<i class="bi bi-arrow-left me-1"></i> Kembali
</a>
<a href="{{ route('progress.export', ['id' => $master->id]) }}" class="btn btn-outline-secondary round">
<i class="bi bi-download me-1"></i> Export CSV
</a>
</div>
</div>

{{-- Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card elev-1">
            <div class="card-body py-3">
                <div class="eyebrow">Total Modul</div>
                <div class="h5 mb-0">{{ $total }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card elev-1">
            <div class="card-body py-3">
                <div class="eyebrow">Selesai</div>
                <div class="h5 mb-0">{{ $selesai }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card elev-1">
            <div class="card-body py-3">
                <div class="eyebrow">Aktif</div>
                <div class="h5 mb-0">{{ $aktif }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card elev-1">
            <div class="card-body py-3">
                <div class="eyebrow mb-1">Update Terakhir</div>
                <div class="fw-semibold">{{ $lastExact }}</div>
                <div class="small text-muted">{{ $lastHuman ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Progress Bar + Next Item --}}
<div class="card card-toolbar p-4 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="h-section mb-0">
            <i class="bi bi-graph-up-arrow"></i><span>Progress Keseluruhan</span>
        </div>
        <div class="flex-grow-1">
            <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height:20px">
                <div class="progress-bar {{ $barClass }}" style="width: {{ $percent }}%;">{{ $percent }}%</div>
            </div>
        </div>
        @if(!empty($nextItem))
            <div class="eyebrow ms-auto text-end">Selanjutnya:</div>
            <div><span class="badge rounded-pill bg-info">{{ $nextItem->modul->nama ?? ('Modul #'.$nextItem->modul_id) }}</span></div>
        @endif
    </div>
</div>

{{-- Alerts --}}
@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show elev-1" role="alert">
        {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show elev-1" role="alert">
        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Tabel modul --}}
<div class="card p-0">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <div class="h-section">
            <i class="bi bi-list-check"></i><span>Daftar Modul</span>
        </div>
        <div class="subtle d-none d-md-block">Klik simpan untuk memperbarui tanggal</div>
    </div>
    <div class="table-responsive">
        <table class="table table-modern table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:44px">#</th>
                    <th>Modul</th>
                    <th style="width:120px">Status</th>
                    <th style="width:170px">Mulai</th>
                    <th style="width:170px">Selesai</th>
                    <th class="text-end" style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $x)
                    @php
                        $isDone   = ($x->akhir_tanggal)
                                 || in_array(strtolower($x->status ?? ''), ['selesai','done','complete','completed','ended']);
                        $startVal = optional($x->mulai_tanggal)->format('Y-m-d');
                        $endVal   = optional($x->akhir_tanggal)->format('Y-m-d');
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">
                            {{ $x->modul->nama ?? ('Modul #'.$x->modul_id) }}
                            @if(!empty($x->modul?->deskripsi))
                                <div class="small text-muted">{{ $x->modul->deskripsi }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $isDone ? 'text-bg-success' : 'text-bg-warning' }}">{{ $isDone ? 'Selesai' : 'Aktif' }}</span>
                        </td>
                        <td class="text-nowrap">
                            <form action="{{ route('progress.updateDates', [$master->id, $x->id]) }}" method="post" class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="date" name="mulai_tanggal" class="form-control form-control-sm input-soft" value="{{ $startVal }}">
                            </td>
                            <td class="text-nowrap">
                                <input type="date" name="akhir_tanggal" class="form-control form-control-sm input-soft" value="{{ $endVal }}">
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-primary round">
                                        <i class="bi bi-save"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split round" data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if(!$isDone)
                                            <li>
                                                <button class="dropdown-item"
                                                        onclick="this.closest('form').querySelector('[name=akhir_tanggal]').value='{{ now()->format('Y-m-d') }}'; this.closest('form').submit(); return false;">
                                                    Tandai Selesai (hari ini)
                                                </button>
                                            </li>
                                        @else
                                            <li>
                                                <button class="dropdown-item"
                                                        onclick="this.closest('form').querySelector('[name=akhir_tanggal]').value=''; this.closest('form').submit(); return false;">
                                                    Batalkan Selesai
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="text-center text-muted py-4">Belum ada modul yang ditugaskan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
