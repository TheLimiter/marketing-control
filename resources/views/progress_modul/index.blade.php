@extends('layouts.app')

@php
    use Carbon\Carbon;

    // Query-string
    $search   = request('q', $search ?? '');
    $status   = request('status');
    $sort     = request('sort','updated_desc');
    $perPage  = (int) request('per_page', $rows->perPage() ?? 15);

    // Ringkasan halaman berjalan
    $totalSekolah = $rows->count();
    $totalModul   = $rows->sum('total_modul');
    $totalSelesai = $rows->sum('selesai');
    $avgProgress  = $totalSekolah ? round($rows->avg('progress_percent')) : 0;

    // Helper tampilan
    $progressClass = fn($p)=> $p>=100?'bg-success':($p>=75?'bg-primary':($p>=50?'bg-info':($p>=25?'bg-warning':'bg-secondary')));
    $statusBadge   = fn($p)=> $p>=100?['Selesai','success']:($p>=75?['On Track','primary']:($p>=25?['Berjalan','warning']:($p>0?['Baru Mulai','info']:['Belum Mulai','secondary'])));
@endphp

@section('content')
    {{-- HIGHLIGHT KOLOM: lokal saja --}}
    <style>
      .cell-warning, .cell-danger { position: relative; }
      /* full-cell tint yang ringan + strip kiri agar “ke-notice” */
      .cell-warning { background: rgba(245,158,11,.10) !important; box-shadow: inset 4px 0 0 0 #f59e0b; }
      .cell-danger  { background: rgba(239,68,68,.12) !important; box-shadow: inset 4px 0 0 0 #ef4444; }
    </style>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Progress Modul Sekolah</div>
            <div class="subtle">Monitoring • Status instalasi modul per sekolah</div>
        </div>
        <a href="{{ route('penggunaan-modul.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah Penggunaan
        </a>
    </div>

    {{-- Ringkasan cepat --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3">
            <div class="eyebrow">Sekolah (halaman ini)</div>
            <div class="fs-5 fw-semibold">{{ $totalSekolah }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3">
            <div class="eyebrow">Total Modul</div>
            <div class="fs-5 fw-semibold">{{ number_format($totalModul) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3">
            <div class="eyebrow">Selesai</div>
            <div class="fs-5 fw-semibold">{{ number_format($totalSelesai) }}</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3">
            <div class="eyebrow mb-1 d-flex justify-content-between align-items-center">
                <span>Rata-rata Progress</span>
                <span class="text-secondary">{{ $avgProgress }}%</span>
            </div>
            <div class="progress" style="height:8px">
                <div class="progress-bar {{ $progressClass($avgProgress) }}" style="width: {{ $avgProgress }}%"></div>
            </div>
        </div></div></div>
    </div>

    {{-- Filter --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari Sekolah</label>
                <input type="text" name="q" value="{{ $search }}" class="input-soft" placeholder="Ketik nama sekolah…">
            </div>
            <div class="field" style="min-width:200px">
                <label>Status</label>
                <select name="status" class="select-soft">
                    <option value="">Semua Status</option>
                    <option value="done"    @selected($status==='done')>Selesai (100%)</option>
                    <option value="ontrack" @selected($status==='ontrack')>On Track (≥75%)</option>
                    <option value="berjalan"@selected($status==='berjalan')>Berjalan (≥25%)</option>
                    <option value="baru"    @selected($status==='baru')>Baru Mulai (>0%)</option>
                    <option value="belum"   @selected($status==='belum')>Belum Mulai (0%)</option>
                </select>
            </div>
            <div class="field" style="min-width:220px">
                <label>Urutkan</label>
                <select name="sort" class="select-soft">
                    <option value="updated_desc"  @selected($sort==='updated_desc')>Update Terakhir ↓</option>
                    <option value="updated_asc"   @selected($sort==='updated_asc')>Update Terakhir ↑</option>
                    <option value="progress_desc" @selected($sort==='progress_desc')>Progress ↓</option>
                    <option value="progress_asc"  @selected($sort==='progress_asc')>Progress ↑</option>
                    <option value="school_asc"    @selected($sort==='school_asc')>Sekolah A→Z</option>
                    <option value="school_desc"   @selected($sort==='school_desc')>Sekolah Z→A</option>
                </select>
            </div>
            <div class="field" style="min-width:150px">
                <label>Per Halaman</label>
                <select name="per_page" class="select-soft" onchange="this.form.submit()">
                    @foreach([10,15,25,50] as $pp)
                        <option value="{{ $pp }}" @selected($perPage===$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ms-auto d-flex align-items-end gap-2">
                <button class="btn btn-primary round"><i class="bi bi-filter me-1"></i> Terapkan</button>
                @if(request()->hasAny(['q','status','sort']) || (request('per_page') && $perPage != 15))
                    <a href="{{ route('progress.index') }}" class="btn btn-ghost round"><i class="bi bi-x-circle me-1"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-modern table-compact table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width:240px">Sekolah</th>
                        <th class="text-center" style="width:10%;">Total Modul</th>
                        <th class="text-center" style="width:10%;">Selesai</th>
                        <th style="width:30%;">Progress</th>
                        <th style="min-width:160px;">Update Terakhir</th>
                        <th class="text-end" style="width:10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        @php
                            // $rows berasal dari MasterSekolah, jadi $r->id adalah ID master
                            $masterId = $r->id;
                            $p = (int) ($r->progress_percent ?? 0);
                            [$label,$variant] = $statusBadge($p);

                            $updatedHuman = $r->last_update ? Carbon::parse($r->last_update)->diffForHumans() : '—';
                            $updatedExact = $r->last_update ? Carbon::parse($r->last_update)->format('d/m/Y H:i') : '—';

                            $isStale = $p < 100 && $r->last_update && Carbon::parse($r->last_update)->lt(now()->subDays(7));

                            // indikator agregat (dari controller)
                            $isOverdueRow = (int)($r->overdue_cnt ?? 0) > 0;
                            $isAgingRow   = !$isOverdueRow && (int)($r->aging_cnt ?? 0) > 0;
                            $ageCellClass = $isOverdueRow ? 'cell-danger' : ($isAgingRow ? 'cell-warning' : '');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    <a href="{{ route('progress.show', ['master' => $masterId]) }}" class="text-decoration-none">
                                        {{ $r->nama_sekolah ?? '—' }}
                                    </a>
                                </div>
                                <div class="small text-muted">
                                    <span class="badge rounded-pill text-bg-{{ $variant }}">{{ $label }}</span>
                                    @if(!empty($r->jenjang))
                                        • <span class="small text-uppercase">{{ $r->jenjang }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $r->total_modul }}</td>
                            <td class="text-center">{{ $r->selesai }}</td>

                            {{-- kolom progress diberi highlight --}}
                            <td class="{{ $ageCellClass }}">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" role="progressbar" aria-valuenow="{{ $p }}" aria-valuemin="0" aria-valuemax="100" style="height:18px">
                                        <div class="progress-bar {{ $progressClass($p) }}" style="width: {{ $p }}%;">
                                            {{ $p }}%
                                        </div>
                                    </div>
                                    @if($isStale)
                                        <span class="badge text-bg-danger" title="Terakhir update {{ $updatedHuman }} ({{ $updatedExact }})">Stagnan</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="small" title="{{ $updatedExact }}">{{ $updatedHuman }}</div>
                                <div class="text-muted small">{{ $updatedExact }}</div>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary round"
                                   href="{{ route('progress.show', ['master' => $masterId]) }}">
                                    <i class="bi bi-eye me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-5">
                                    <div class="mb-2">📭</div>
                                    <div class="fw-semibold">Belum ada penggunaan modul.</div>
                                    <div class="text-muted small mb-3">Tambahkan penggunaan modul untuk mulai memantau progress sekolah.</div>
                                    <a href="{{ route('penggunaan-modul.create') }}" class="btn btn-sm btn-primary round">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Penggunaan
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <div class="small text-muted">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }} data</div>
        <div>{{ $rows->appends(request()->query())->links() }}</div>
    </div>
@endsection
