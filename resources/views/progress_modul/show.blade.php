@extends('layouts.app')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    // Data dari controller
    $total   = (int) ($total ?? 0);
    $selesai = (int) ($selesai ?? 0);
    $aktif   = (int) ($aktif ?? 0);

    // Persiapan data update terakhir
    $last      = $lastUpdate ?? null;
    $lastHuman = '-';
    if ($last) {
        try { $lastHuman = Carbon::parse($last)->diffForHumans(); } catch (\Throwable $e) {}
    }

    // Opsi untuk dropdown & offcanvas
    $stageOptions = \App\Models\PenggunaanModul::stageOptions();
    $badgeColors  = [
        'modul_progress' => 'info',
        'modul_done'     => 'success',
        'modul_reopen'   => 'warning',
        'modul_attach'   => 'secondary',
        'stage_change'   => 'dark',
    ];
@endphp

@push('styles')
<style>
/* =========================================================
   Table layout improvements (focus: Hasil column)
   ========================================================= */
:root{
    --border-color: #e5e7eb;
    --header-bg: #f9fafb;
    --row-hover-bg: #f9fafb;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --radius: 8px;
}
.table-container { border: 1px solid var(--border-color); border-radius: var(--radius); overflow-x: auto; background-color: #fff; }
.static-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto; /* biarkan browser menyesuaikan */
}
.static-table th, .static-table td {
    padding: 12px 15px;
    vertical-align: middle; /* default: tengah, override per-kolom bila perlu */
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    word-break: break-word;
    white-space: normal;
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
.static-table tbody tr:last-child td { border-bottom: none; }
.static-table tbody tr:hover { background-color: var(--row-hover-bg); }

/* Khusus kolom Hasil: mulai dari atas sel supaya tidak 'mengambang' */
.col-hasil { vertical-align: top; }

/* Pastikan judul Hasil tidak memberi margin/line-height ekstra */
.static-table td .fw-semibold { margin: 0; line-height: 1.25; }

/* Ellipsis multi-line untuk Hasil (2 baris). Ubah -webkit-line-clamp ke 3 jika mau 3 baris */
.ellipsis-wrapper {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    max-width: 100%;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }
.empty-state-cell { text-align: center; padding: 40px; }

/* Badge styling */
.badge-stage{ border-radius:9999px; font-size:12px; padding:.25rem .5rem; border:1px solid transparent; font-weight:600; display:inline-block; line-height:1; vertical-align: middle; }
.badge-stage.info{ background:#ecfeff; color:#0f766e; border-color:#a7f3d0; }
.badge-stage.success{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.warning{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
.badge-stage.danger{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.badge-stage.secondary{ background:#f3f4f6; color:#111827; border-color:#e5e7eb; }
.badge-stage.dark{ background:#eef2ff; color:#0f172a; border-color:#d1d5db; }
.badge-stage.primary{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
</style>
@endpush

@section('content')
{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="h-page">{{ $master->nama_sekolah }}</div>
        <div class="subtle">Progress & Aktivitas Modul</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('progress.index') }}" class="btn btn-ghost round"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <button class="btn btn-outline-secondary round" data-bs-toggle="offcanvas" data-bs-target="#offcanvasStage"><i class="bi bi-sliders me-1"></i> Kelola Stage</button>
        <button class="btn btn-primary round" data-bs-toggle="offcanvas" data-bs-target="#ocNewActivity"><i class="bi bi-plus-lg me-1"></i> Tambah Aktivitas</button>
    </div>
</div>

{{-- Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Total Modul</div><div class="h5 mb-0">{{ $total }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Selesai</div><div class="h5 mb-0">{{ $selesai }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Aktif</div><div class="h5 mb-0">{{ $aktif }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card elev-1"><div class="card-body py-3"><div class="eyebrow">Update Terakhir</div><div class="h5 mb-0">{{ $lastHuman }}</div></div></div></div>
</div>

{{-- ============ BLOK 1: DAFTAR MODUL & STAGE ============ --}}
<div class="h-section">Daftar Modul & Stage</div>
<div class="card p-0 mb-4">
    <div class="table-container">
        <table class="static-table align-middle">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Modul</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%;">Periode</th>
                    <th style="width: 15%;">Stage</th>
                    <th style="width: 15%;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $x)
                @php
                    $isDone      = $x->isDone();
                    $statusText  = $isDone ? 'Selesai' : 'Aktif';
                    $statusClass = $isDone ? 'success' : 'info';
                    if (!$isDone && $x->akhir_tanggal?->isPast()) {
                        $statusText  = 'Lewat Perkiraan';
                        $statusClass = 'danger';
                    }
                    $stageBadge = $x->stage_badge_class ?? 'secondary';
                    $stageLabel = $x->stage_label ?? '—';
                @endphp
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $x->modul->nama ?? ('Modul #'.$x->modul_id) }}</td>
                    <td><span class="badge-stage {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td class="small-muted">
                        {{ $x->mulai_tanggal ? $x->mulai_tanggal->format('d M Y') : '...' }} - {{ $x->akhir_tanggal ? $x->akhir_tanggal->format('d M Y') : '...' }}
                    </td>
                    <td><span class="badge-stage {{ $stageBadge }}">{{ $stageLabel }}</span></td>
                    <td class="text-center">
                       <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">Aksi <i class="bi bi-chevron-down"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#editModulUsageModal"
                                            data-form-action="{{ route('progress.updateDates', [$master->id, $x->id]) }}"
                                            data-stage-action="{{ route('progress.stage.update', [$master->id, $x->id]) }}"
                                            data-modul-nama="{{ $x->modul->nama ?? 'Modul #'.$x->modul_id }}"
                                            data-mulai="{{ optional($x->mulai_tanggal)->format('Y-m-d') }}"
                                            data-akhir="{{ optional($x->akhir_tanggal)->format('Y-m-d') }}"
                                            data-stage="{{ $x->stage_modul }}">
                                        <i class="bi bi-pencil me-2"></i>Ubah Tanggal/Stage
                                    </button>
                                </li>
                                <li>
                                    <form action="{{ route('progress.toggle', [$master->id, $x->id]) }}" method="post">
                                    @csrf
                                        <button type="submit" class="dropdown-item text-{{ $isDone ? 'warning' : 'success' }}">
                                            <i class="bi bi-check2-circle me-2"></i>{{ $isDone ? 'Batalkan Selesai' : 'Tandai Selesai' }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state-cell">Belum ada modul yang ditugaskan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============ BLOK 2: LIST AKTIVITAS ============ --}}
<div class="h-section">Aktivitas Modul</div>
<div class="card p-0">
    <form method="get" class="card-toolbar">
        <div class="toolbar">
            <div class="field"><label>Dari</label><input type="date" name="from" value="{{ request('from') }}" class="input-soft"></div>
            <div class="field"><label>Sampai</label><input type="date" name="to" value="{{ request('to') }}" class="input-soft"></div>
            <div class="field flex-grow-1"><label>Cari</label><input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Hasil atau catatan..."></div>
            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-ghost round"><i class="bi bi-x-circle me-1"></i> Reset</a>
            </div>
        </div>
    </form>
    <div class="table-container">
        <table class="static-table align-middle">
            <thead>
                <tr>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 15%;">Jenis</th>
                    <th style="width: 25%;">Hasil</th>
                    <th style="width: 30%;">Catatan</th>
                    <th style="width: 15%;">Oleh</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logAkt as $it)
                @php $k = strtolower($it->jenis ?? 'modul_progress'); @endphp
                <tr>
                    <td class="small-muted">
                        <div class="fw-semibold">{{ optional($it->tanggal)->format('d M Y') ?: '-' }}</div>
                        <div>{{ optional($it->created_at)->diffForHumans() }}</div>
                    </td>
                    <td><span class="badge-stage {{ $badgeColors[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span></td>
                    <td class="fw-semibold col-hasil">
                        <div class="ellipsis-wrapper">{{ $it->hasil }}</div>
                    </td>
                    <td class="small-muted">{!! $it->catatan ? nl2br(e($it->catatan)) : '-' !!}</td>
                    <td class="small-muted">{{ optional($it->creator)->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state-cell">Belum ada aktivitas modul yang tercatat.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($logAkt->hasPages())
    <div class="p-3 border-top">{{ $logAkt->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Modal: Edit Penggunaan Modul --}}
<div class="modal fade" id="editModulUsageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body form--soft">
                <form id="formUpdateDates" method="post" class="mb-4">
                    @csrf
                    <h6 class="mb-3">Ubah Periode</h6>
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Mulai</label><input type="date" name="mulai_tanggal" class="form-control input-soft"></div>
                        <div class="col-6"><label class="form-label">Akhir</label><input type="date" name="akhir_tanggal" class="form-control input-soft"></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm round mt-3">Simpan Tanggal</button>
                </form>
                <hr class="my-4">
                <form id="formUpdateStage" method="post">
                    @csrf
                    @method('PATCH')
                    <h6 class="mb-3">Ubah Stage Penggunaan</h6>
                    <select name="stage_modul" class="form-select select-soft">
                        @foreach($stageOptions as $val => $lab)
                            <option value="{{ $val }}">{{ $lab }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm round mt-3">Simpan Stage</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{--
    ================================================================
    MEMUAT KODE OFF CANVAS DARI FILE TERPISAH
    Ini adalah satu-satunya perubahan yang diperlukan.
    ================================================================
--}}
@include('progress_modul._offcanvas_forms')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editModulUsageModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const data = button.dataset;

            const modalTitle = editModal.querySelector('.modal-title');
            const dateForm = editModal.querySelector('#formUpdateDates');
            const stageForm = editModal.querySelector('#formUpdateStage');

            modalTitle.textContent = `Ubah: ${data.modulNama}`;

            dateForm.action = data.formAction;
            stageForm.action = data.stageAction;

            dateForm.querySelector('input[name="mulai_tanggal"]').value = data.mulai;
            dateForm.querySelector('input[name="akhir_tanggal"]').value = data.akhir;
            stageForm.querySelector('select[name="stage_modul"]').value = data.stage;
        });
    }
});
</script>
@endpush
