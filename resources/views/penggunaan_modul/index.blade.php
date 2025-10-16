@extends('layouts.app')

@php
    // Definisikan mapping di sini agar view lebih bersih
    $statusClasses = [
        'ended'       => 'tolak',    // Merah
        'paused'      => 'warning',  // Kuning
        'done'        => 'success',  // Hijau
        'on_progress' => 'info',     // Biru muda
        'reopen'      => 'warning',  // Kuning
        'active'      => 'slth',     // Biru solid
    ];
@endphp

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout for Penggunaan Modul
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
    table-layout: fixed;
}

.static-table th,
.static-table td {
    padding: 12px 15px;
    vertical-align: middle;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
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
.col-sekolah  { width: 250px; }
.col-pengguna { width: 200px; }
.col-modul    { width: 200px; }
.col-periode  { width: 180px; }
.col-status   { width: 180px; }
.col-aksi     { width: 100px; text-align: center; }


/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

/* Highlight Kolom */
.cell-warning { background: rgba(245,158,11,.08); box-shadow: inset 3px 0 0 0 #f59e0b; }
.cell-danger  { background: rgba(239,68,68,.08); box-shadow: inset 3px 0 0 0 #ef4444; }


/* Empty state */
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
            <div class="h-page">Penggunaan Modul</div>
            <div class="subtle">Daftar assignment modul per sekolah</div>
        </div>
        <a href="{{ route('penggunaan-modul.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </a>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field" style="min-width:210px">
                <label>Modul</label>
                <select name="modul_id" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua Modul</option>
                    @foreach($modulOptions as $m)
                        <option value="{{ $m->id }}" @selected((string)request('modul_id') === (string)$m->id)>{{ $m->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Lisensi</label>
                <select name="lisensi" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua Lisensi</option>
                    <option value="trial"    @selected(request('lisensi') === 'trial')>Uji Coba</option>
                    <option value="official" @selected(request('lisensi') === 'official')>Official</option>
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Status</label>
                <select name="status" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach(['active'=>'Active','paused'=>'Paused','ended'=>'Ended'] as $k=>$v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="min-width:170px">
                <label>Stage Sekolah</label>
                <select name="stage" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua Stage</option>
                    @foreach($stageOptions as $k=>$v)
                        <option value="{{ $k }}" @selected((string)request('stage') === (string)$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari Sekolah</label>
                <input class="input-soft" name="q" value="{{ request('q') }}" placeholder="Nama sekolah">
            </div>

            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="{{ route('penggunaan-modul.index') }}" class="btn btn-ghost round">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="card p-0">
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-sekolah">Sekolah</th>
                        <th class="col-pengguna">Pengguna</th>
                        <th class="col-modul">Modul</th>
                        <th class="col-periode">Periode & Update</th>
                        <th class="col-status">Lisensi / Status</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $r)
                        @php
                            $status = $r->computed_status ?? $r->status ?? 'unknown';
                            $statusClass = $statusClasses[$status] ?? 'secondary';

                            $today = now()->startOfDay();
                            $isOverdue = $r->akhir_tanggal && $r->akhir_tanggal->lt($today) && ($r->status !== 'ended');
                            $isAging = !$isOverdue && $r->mulai_tanggal && $r->mulai_tanggal->lte($today->copy()->subDays(7));
                            $ageCellClass = $isOverdue ? 'cell-danger' : ($isAging ? 'cell-warning' : '');
                        @endphp
                        <tr>
                            <td class="col-sekolah">
                                <a href="{{ route('master.edit', $r->master_sekolah_id) }}" class="text-decoration-none fw-semibold ellipsis-wrapper">
                                    {{ $r->master->nama_sekolah ?? '-' }}
                                </a>
                                <div class="small-muted ellipsis-wrapper">{{ $r->master->alamat ?? '' }}</div>
                            </td>
                            <td class="col-pengguna">
                                <div class="ellipsis-wrapper small-muted">{{ $r->pengguna_nama ?: '-' }}</div>
                                <div class="ellipsis-wrapper small-muted">{{ $r->pengguna_kontak ?: '-' }}</div>
                            </td>
                            <td class="col-modul">
                                <div class="fw-semibold ellipsis-wrapper">{{ $r->modul->nama ?? '-' }}</div>
                            </td>
                            <td class="col-periode {{ $ageCellClass }}">
                                <div class="small-muted ellipsis-wrapper">{{ $r->mulai_tanggal ? $r->mulai_tanggal->format('d/m/y') : '...' }} - {{ $r->akhir_tanggal ? $r->akhir_tanggal->format('d/m/y') : '...' }}</div>
                                <div class="small-muted ellipsis-wrapper">Update: {{ $r->last_used_at ? $r->last_used_at->diffForHumans() : '-' }}</div>
                            </td>
                            <td class="col-status">
                                <span class="badge-stage {{ $r->is_official ? 'mou' : 'secondary' }}">
                                    {{ $r->lisensi_label ?? ($r->is_official ? 'Official' : 'Trial') }}
                                </span>
                                <span class="badge-stage {{ $statusClass }}">{{ ucfirst(str_replace('_',' ', $status)) }}</span>
                            </td>
                            <td class="col-aksi">
                                <div class="dropdown">
                                   <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">
                                       Aksi <i class="bi bi-chevron-down"></i>
                                   </button>
                                   <ul class="dropdown-menu dropdown-menu-end">
                                       <li>
                                           <form method="post" action="{{ route('penggunaan-modul.use', $r->id) }}">
                                               @csrf
                                               <button type="submit" class="dropdown-item" title="Catat penggunaan sekarang">
                                                   <i class="bi bi-clock me-2"></i>Gunakan Sekarang
                                               </button>
                                           </form>
                                       </li>
                                       <li><hr class="dropdown-divider"></li>
                                       @can('update', $r)
                                           @if($r->status === 'active' || $r->status === 'reopen' || $r->status === 'on_progress')
                                           <li>
                                               <form method="post" action="{{ route('penggunaan-modul.done', $r->id) }}">
                                                   @csrf
                                                   <button type="submit" class="dropdown-item">
                                                       <i class="bi bi-check-circle me-2"></i>Tandai Selesai
                                                   </button>
                                               </form>
                                           </li>
                                           @endif
                                           @if($r->status === 'done')
                                           <li>
                                               <form method="post" action="{{ route('penggunaan-modul.reopen', $r->id) }}">
                                                   @csrf
                                                   <button type="submit" class="dropdown-item">
                                                       <i class="bi bi-arrow-counterclockwise me-2"></i>Buka Kembali
                                                   </button>
                                               </form>
                                           </li>
                                           @endif
                                       @endcan
                                       <li><a class="dropdown-item" href="{{ route('penggunaan-modul.edit', $r->id) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                       <li><hr class="dropdown-divider"></li>
                                       @can('delete', $r)
                                       <li>
                                           <form method="post" action="{{ route('penggunaan-modul.destroy', $r->id) }}" onsubmit="return confirm('Hapus penggunaan modul ini?')">
                                               @csrf @method('DELETE')
                                               <button type="submit" class="dropdown-item text-danger">
                                                   <i class="bi bi-trash me-2"></i>Hapus
                                               </button>
                                           </form>
                                       </li>
                                       @endcan
                                   </ul>
                               </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state-cell">
                                <div class="fw-semibold">Belum ada penggunaan modul.</div>
                                <div class="small-muted">Gunakan filter untuk mencari data.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($items->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} data</div>
            <div>{{ $items->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>
</div>
@endsection

