@extends('layouts.app')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    // --- Data dari Controller ---
    $search       = request('q', $search ?? '');
    $sort         = request('sort', 'updated_desc');
    $perPage      = (int) request('per_page', 15);
    $totalSekolah = $items->total() ?? 0;

    // --- Data Ringkasan ---
    $summaryData  = $summary ?? [];
    $totalModul   = (int) ($summaryData['total_modul'] ?? 0);
    $totalSelesai = (int) ($summaryData['total_selesai'] ?? 0);
    $avgProgress  = $totalSekolah ? round((float)($summaryData['avg_progress'] ?? 0)) : 0;

    // --- Helper Tampilan ---
    $statusBadge = function (int $p) {
        if ($p >= 100) return ['Selesai', 'success'];
        if ($p >= 75) return ['On Track', 'primary'];
        if ($p >= 25) return ['Berjalan', 'warning'];
        if ($p > 0) return ['Baru Mulai', 'info'];
        return ['Belum Mulai', 'secondary'];
    };

    $stageBadgeColors = ['dilatih' => 'secondary', 'didampingi' => 'info', 'mandiri' => 'success'];
    $stageSummary = function($row) use ($stageBadgeColors) {
        $dil = (int) ($row->cnt_dilatih ?? 0);
        $did = (int) ($row->cnt_didampingi ?? 0);
        $man = (int) ($row->cnt_mandiri ?? 0);
        $tot = (int) ($row->total_modul ?? 0);
        $counts = "Dilatih:$dil · Didampingi:$did · Mandiri:$man";

        if ($tot === 0) return ['label' => null, 'badge' => 'secondary', 'title' => '—', 'counts' => $counts];
        if ($man > 0 && $man >= $did && $man >= $dil) return ['label' => 'Mandiri', 'badge' => $stageBadgeColors['mandiri'], 'title' => $counts, 'counts' => $counts];
        if ($did > 0 && $did >= $dil) return ['label' => 'Didampingi', 'badge' => $stageBadgeColors['didampingi'], 'title' => $counts, 'counts' => $counts];
        return ['label' => 'Dilatih', 'badge' => $stageBadgeColors['dilatih'], 'title' => $counts, 'counts' => $counts];
    };
@endphp

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout
   ========================================================= */
:root {
    --border-color: #e5e7eb;
    --header-bg: #f9fafb;
    --row-hover-bg: #f9fafb;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --radius: 8px;
}
.table-container {
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow-x: auto;
    background-color: #fff;
}
.static-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed; /* Kunci utama agar lebar kolom konsisten */
}
.static-table th,
.static-table td {
    padding: 12px 15px;
    vertical-align: middle;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    /* FIX: Mencegah teks terlalu panjang merusak layout */
    word-break: break-word;
}
.static-table th {
    background-color: var(--header-bg);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--text-secondary);
    position: sticky;
    top: 0;
    z-index: 1;
}
.static-table tbody tr:last-child td {
    border-bottom: none;
}
.static-table tbody tr:hover {
    background-color: var(--row-hover-bg);
}

/* Penentuan Lebar Kolom */
.col-sekolah { width: 280px; }
.col-modul   { width: 110px; }
.col-stage   { width: 220px; }
.col-update  { width: 160px; }
.col-catatan { width: 250px; }
.col-aksi    { width: 100px; text-align: center; }

/* Override Perataan Teks */
.static-table .col-modul,
.static-table .col-aksi {
    text-align: center;
}

/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }
.text-mono { font-variant-numeric: tabular-nums; }
.empty-state-cell {
    text-align: center;
    padding: 40px;
}
</style>
@endpush

@section('content')
<div class="anima-scope">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Progress Modul Sekolah</div>
            <div class="subtle">Monitoring status penggunaan modul per sekolah</div>
        </div>
    </div>

    {{-- Ringkasan cepat --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Total Sekolah</div><div class="fs-5 fw-semibold">{{ $totalSekolah }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Total Modul Terpasang</div><div class="fs-5 fw-semibold">{{ number_format($totalModul) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Total Modul Selesai</div><div class="fs-5 fw-semibold">{{ number_format($totalSelesai) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Rata-rata Kemajuan</div><div class="fs-5 fw-semibold">{{ $avgProgress }}%</div></div></div></div>
    </div>

    {{-- Filter --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px"><label>Cari Sekolah</label><input type="text" name="q" value="{{ $search }}" class="input-soft" placeholder="Ketik nama sekolah"></div>
            <div class="field" style="min-width:220px"><label>Urutkan</label><select name="sort" class="select-soft" onchange="this.form.submit()"><option value="updated_desc" @selected($sort === 'updated_desc')>Update Terakhir ↓</option><option value="updated_asc" @selected($sort === 'updated_asc')>Update Terakhir ↑</option><option value="progress_desc" @selected($sort === 'progress_desc')>Progress ↓</option><option value="progress_asc" @selected($sort === 'progress_asc')>Progress ↑</option><option value="school_asc" @selected($sort === 'school_asc')>Sekolah A–Z</option><option value="school_desc" @selected($sort === 'school_desc')>Sekolah Z–A</option></select></div>
            <div class="ms-auto d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary round"><i class="bi bi-filter me-1"></i> Terapkan</button>@if(request()->hasAny(['q','status','sort']))<a href="{{ route('progress.index') }}" class="btn btn-ghost round"><i class="bi bi-x-circle me-1"></i> Reset</a>@endif</div>
        </div>
    </form>

    {{-- Tabel Static --}}
    <div class="card p-0">
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-sekolah">Sekolah</th>
                        <th class="col-modul">Total Modul</th>
                        <th class="col-stage">Stage Penggunaan</th>
                        <th class="col-update">Update Terakhir</th>
                        <th class="col-catatan">Catatan Terakhir</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $masterId = $item->master_sekolah_id ?? $item->id;
                            $progress = (int) ($item->progress_percent ?? 0);
                            [$progressLabel, $progressVariant] = $statusBadge($progress);

                            $lastUpdate = $item->last_update ?? $item->last_used_at;
                            $updatedHuman = $lastUpdate ? Carbon::parse($lastUpdate)->diffForHumans() : '—';
                            $updatedExact = $lastUpdate ? Carbon::parse($lastUpdate)->format('d/m/Y H:i') : '—';

                            $stage = $stageSummary($item);

                            // Logika untuk mengambil catatan terakhir
                            $lastNote = '-';
                            if (!empty($item->latest_activity_data)) {
                                [$catatan, $hasil] = array_pad(explode('|||', $item->latest_activity_data, 2), 2, '');
                                $lastNote = trim($catatan) ?: trim($hasil) ?: '-';
                            }
                        @endphp
                        <tr>
                            <td class="col-sekolah">
                                <a href="{{ route('progress.show', ['master' => $masterId]) }}" class="text-decoration-none ellipsis-wrapper fw-semibold">{{ $item->nama_sekolah ?? '-' }}</a>
                                <div class="small-muted">
                                    <span class="badge-stage {{ $progressVariant }}">{{ $progressLabel }}</span>
                                </div>
                            </td>
                            <td class="col-modul text-mono fw-bold fs-5">{{ $item->total_modul ?? 0 }}</td>
                            <td class="col-stage">
                                @if($stage['label'])
                                    <span class="badge-stage {{ $stage['badge'] }}" title="{{ $stage['counts'] }}">{{ $stage['label'] }}</span>
                                @else
                                    <span class="badge-stage secondary">—</span>
                                @endif
                            </td>
                            <td class="col-update" title="{{ $updatedExact }}">
                                <div class="ellipsis-wrapper">{{ $updatedHuman }}</div>
                                <div class="small-muted ellipsis-wrapper">{{ $updatedExact }}</div>
                            </td>
                            <td class="col-catatan">
                                <div class="ellipsis-wrapper small-muted" title="{{ $lastNote }}">
                                    {{ Str::limit($lastNote, 80) }}
                                </div>
                            </td>
                            <td class="col-aksi">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">
                                        Aksi <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('progress.show', ['master' => $masterId]) }}"><i class="bi bi-eye me-2"></i>Detail Progress</a></li>
                                        <li><a class="dropdown-item" href="{{ route('master.aktivitas.index', $masterId) }}"><i class="bi bi-clock-history me-2"></i>Lihat Aktivitas</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('master.edit', $masterId) }}"><i class="bi bi-pencil me-2"></i>Edit Sekolah</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state-cell">
                                <div class="mb-2 fs-4">📑</div>
                                <div class="fw-semibold">Belum ada data progress sekolah.</div>
                                <div class="text-muted small">Data akan muncul di sini setelah sekolah menggunakan modul.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} data</div>
            <div>{{ $items->appends(request()->query())->links() }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
