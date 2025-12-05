@extends('layouts.app')

@php
    use App\Models\MasterSekolah as MS;

    // Mapping stage -> label
    $stageOptions = [
        MS::ST_CALON  => 'Calon',
        MS::ST_SHB    => 'Sudah Dihubungi',
        MS::ST_SLTH   => 'Sudah Dilatih',
        MS::ST_MOU    => 'MOU Aktif',
        MS::ST_TLMOU  => 'Tindak Lanjut MOU',
        MS::ST_TOLAK  => 'Ditolak',
    ];

    // Mapping stage -> class badge
    $stageClass = [
        MS::ST_CALON  => 'calon',
        MS::ST_SHB    => 'shb',
        MS::ST_SLTH   => 'slth',
        MS::ST_MOU    => 'mou',
        MS::ST_TLMOU  => 'tlmou',
        MS::ST_TOLAK  => 'tolak',
    ];
@endphp

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout for Master Sekolah
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
.col-name   { width: 280px; }
.col-kontak { width: 200px; }
.col-stage  { width: 220px; }
.col-mou    { width: 110px; }
.col-ttd    { width: 100px; }
.col-update { width: 140px; }
.col-aksi   { width: 100px; text-align: center; }

/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

/* Badge styling */
.badge-stage{ border-radius:9999px; font-size:12px; padding:.3rem .55rem; border:1px solid transparent; font-weight:600; }
.badge-stage.calon{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
.badge-stage.shb{ background:#ecfeff; color:#155e75; border-color:#a5f3fc; }
.badge-stage.slth{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.mou{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.tlmou{ background:#fefce8; color:#854d0e; border-color:#fde68a; }
.badge-stage.tolak{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.badge-stage.secondary{ background:#f3f4f6; color:#111827; border-color:#e5e7eb; }

/* Stage Dropdown Indicator */
.stage-interactive-wrapper { display: flex; align-items: center; gap: 8px; }
.stage-interactive-wrapper .dropdown-toggle { opacity: 0; transition: opacity 0.2s ease-in-out; }
.static-table tr:hover .stage-interactive-wrapper .dropdown-toggle { opacity: 1; }

/* Empty state */
.empty-state-cell { text-align: center; padding: 40px; }

/* Offcanvas Redesign */
.offcanvas-body .data-group { margin-bottom: 1.5rem; }
.offcanvas-body .data-label { font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.25rem; display: flex; align-items: center; gap: 6px;}
.offcanvas-body .data-value { font-weight: 500; color: var(--text-primary); }
.offcanvas-footer { background-color: #f9fafb; border-top: 1px solid var(--border-color); }
</style>
@endpush

@section('content')
<div class="anima-scope">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Master Sekolah</div>
            <div class="subtle">Kelola pipeline & status kemajuan</div>
        </div>
        {{-- Tombol Pemicu Modal --}}
        <button type="button" class="btn btn-primary round" data-bs-toggle="modal" data-bs-target="#schoolCreateModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Sekolah
        </button>
    </div>

    {{-- Toolbar --}}
    <form method="get" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:260px">
                <label>Cari nama sekolah</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="Ketik nama sekolah...">
            </div>
            <div class="field" style="min-width:200px">
                <label>Stage</label>
                <select name="stage" class="select-soft" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string)request('stage')===(string)$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round">Terapkan</button>
                @if(request()->anyFilled(['q', 'stage']))
                    <a href="{{ route('master.index') }}" class="btn btn-ghost round">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="card p-0">
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-name">Nama Sekolah</th>
                        <th class="col-kontak">Kontak</th>
                        <th class="col-stage">Stage</th>
                        <th class="col-mou">MOU</th>
                        <th class="col-ttd">TTD</th>
                        <th class="col-update">Update</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $x)
                        @php
                            $sid      = $x->id;
                            $mouAda   = !is_null($x->mou_path);
                            $label    = $stageOptions[$x->stage] ?? '-';
                            $cls      = $stageClass[$x->stage]   ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="col-name">
                                <a href="{{ route('master.edit', $sid) }}" class="text-decoration-none fw-semibold ellipsis-wrapper">{{ $x->nama_sekolah ?? '-' }}</a>
                                <div class="small-muted ellipsis-wrapper">{!! $x->alamat ? e($x->alamat) : '&mdash;' !!}</div>
                            </td>
                            <td class="col-kontak">
                                <div class="small-muted ellipsis-wrapper">{{ $x->narahubung ?: '—' }}</div>
                                <div class="small-muted ellipsis-wrapper">{{ $x->no_hp ?: '—' }}</div>
                            </td>
                            <td class="col-stage">
                                <div class="stage-interactive-wrapper">
                                    <span class="badge-stage {{ $cls }}">{{ $label }}</span>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-ghost round dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah Stage"></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach($stageOptions as $val => $lbl)
                                                @if($val !== $x->stage)
                                                <li>
                                                    <form action="{{ route('master.stage.update', $sid) }}" method="POST" onsubmit="return confirm('Ubah stage sekolah ini?')">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="to" value="{{ $val }}">
                                                        <button type="submit" class="dropdown-item">→ {{ $lbl }}</button>
                                                    </form>
                                                </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </td>
                            <td class="col-mou">{!! $mouAda ? '<span class="badge-stage mou">Ada</span>' : '<span class="badge-stage secondary">&mdash;</span>' !!}</td>
                            <td class="col-ttd">{!! $x->ttd_status ? '<span class="badge-stage tlmou">OK</span>' : '<span class="badge-stage secondary">&mdash;</span>' !!}</td>
                            <td class="col-update"><div class="small-muted ellipsis-wrapper" title="{{ optional($x->updated_at)->format('d/m/Y H:i') }}">{{ optional($x->updated_at)->diffForHumans() ?? '—' }}</div></td>
                            <td class="col-aksi">
                                <div class="dropdown">
                                   <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">Aksi <i class="bi bi-chevron-down"></i></button>
                                   <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('master.aktivitas.index', $sid) }}"><i class="bi bi-clock-history me-2"></i>Aktivitas</a></li>
                                            <li><a class="dropdown-item" href="{{ route('progress.show', $sid) }}"><i class="bi bi-graph-up-arrow me-2"></i>Progress</a></li>
                                            <li><a class="dropdown-item" href="{{ route('master.edit',$sid) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            <li>
                                                @if($mouAda)
                                                    {{-- PERBAIKAN: Menggunakan Route Preview agar aman dari Forbidden 403 --}}
                                                    <a class="dropdown-item" target="_blank" href="{{ route('master.mou.preview', $sid) }}"><i class="bi bi-file-earmark-arrow-up me-2"></i>Lihat MOU</a>
                                                @else
                                                    <a class="dropdown-item" href="{{ route('master.mou.form', $sid) }}"><i class="bi bi-cloud-upload me-2"></i>Upload MOU</a>
                                                @endif
                                            </li>
                                            <li><a class="dropdown-item" href="{{ route('master.mou.form', $sid) }}"><i class="bi bi-pencil-square me-2"></i>MOU/TTD</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#schoolDetail"
                                                data-nama="{{ $x->nama_sekolah }}" data-jenjang="{{ $x->jenjang ?? '—' }}"
                                                data-alamat="{{ $x->alamat ?? '—' }}" data-narahubung="{{ $x->narahubung ?? '—' }}"
                                                data-nohp="{{ $x->no_hp ?? '—' }}" data-sumber="{{ $x->sumber ?? '—' }}"
                                                data-siswa="{{ $x->jumlah_siswa ?? '—' }}" data-mou="{{ $mouAda ? 'Ada' : '—' }}"
                                                data-ttd="{{ $x->ttd_status ? 'OK' : '—' }}" data-stage="{{ $label }}"
                                                data-tindak="{{ $x->tindak_lanjut ?? '—' }}" data-catatan="{{ $x->catatan ? \Illuminate\Support\Str::limit($x->catatan, 200) : '—' }}"
                                                data-created="{{ optional($x->created_at)->format('d/m/Y H:i') }}"
                                                data-updated="{{ optional($x->updated_at)->diffForHumans() }}"
                                                data-edit="{{ route('master.edit',$sid) }}"
                                                data-aktivitas="{{ route('master.aktivitas.index',$sid) }}"
                                                data-progress="{{ route('progress.show',$sid) }}"
                                                @if(Route::has('penggunaan-modul.batch-form'))
                                                data-batch="{{ route('penggunaan-modul.batch-form', ['school' => $sid]) }}"
                                                @endif
                                                ><i class="bi bi-info-circle me-2"></i>Detail Cepat
                                            </button></li>
                                   </ul>
                               </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state-cell">Belum ada data sekolah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">Menampilkan {{ $rows->firstItem() }}-{{ $rows->lastItem() }} dari {{ $rows->total() }} hasil</div>
            <div>{{ $rows->appends(request()->query())->links() }}</div>
        </div>
        @endif
    </div>
</div>

{{-- MODAL BARU: Tambah Sekolah --}}
<div class="modal fade" id="schoolCreateModal" tabindex="-1" aria-labelledby="schoolCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('master.store') }}" method="post" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="schoolCreateModalLabel"><i class="bi bi-building-add me-2"></i>Tambah Sekolah Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body form--soft">
                <h6 class="mb-3">Informasi Dasar & Kontak</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Sekolah*</label>
                        <input type="text" name="nama_sekolah" class="form-control input-soft" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenjang</label>
                        <select name="jenjang" class="form-select select-soft">
                            <option value="">Pilih Jenjang</option>
                            <option value="SD">SD/MI</option>
                            <option value="SMP">SMP/MTs</option>
                            <option value="SMA">SMA/MA/SMK</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control input-soft" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Narahubung</label>
                        <input type="text" name="narahubung" class="form-control input-soft">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP/WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control input-soft">
                    </div>
                </div>
                <hr class="my-4">
                <h6 class="mb-3">Catatan & Tindak Lanjut</h6>
                 <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sumber Prospek</label>
                        <input type="text" name="sumber" class="form-control input-soft" placeholder="e.g., Pameran, Telepon, dll.">
                    </div>
                     <div class="col-md-6">
                        <label class="form-label">Jumlah Siswa</label>
                        <input type="number" name="jumlah_siswa" min="0" class="form-control input-soft" placeholder="e.g., 650">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tindak Lanjut</label>
                        <textarea name="tindak_lanjut" rows="2" class="form-control input-soft" placeholder="Rencana follow-up berikutnya"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" rows="4" class="form-control input-soft" placeholder="Ringkasan call/visit, komitmen, hal khusus"></textarea>
                    </div>
                </div>
                <div class="form-text mt-3">Stage awal untuk sekolah baru akan otomatis diatur sebagai "Calon".</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" type="submit">Simpan Sekolah</button>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas Detail Sekolah (REDESIGN) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="schoolDetail" aria-labelledby="schoolDetailLabel">
    <div class="offcanvas-header">
        <div>
            <div class="eyebrow">Detail Cepat</div>
            <h5 class="offcanvas-title mb-0" id="schoolDetailLabel">Nama Sekolah</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="data-group">
            <div class="data-label"><i class="bi bi-geo-alt"></i> Alamat</div>
            <p class="data-value" id="detail-alamat">—</p>
        </div>
        <div class="data-group">
            <div class="row">
                <div class="col-6">
                    <div class="data-label"><i class="bi bi-person"></i> Narahubung</div>
                    <p class="data-value" id="detail-narahubung">—</p>
                </div>
                <div class="col-6">
                    <div class="data-label"><i class="bi bi-whatsapp"></i> Kontak</div>
                    <p class="data-value" id="detail-nohp">—</p>
                </div>
            </div>
        </div>
         <div class="data-group">
            <div class="row">
                <div class="col-6">
                    <div class="data-label"><i class="bi bi-building"></i> Jenjang</div>
                    <p class="data-value" id="detail-jenjang">—</p>
                </div>
                <div class="col-6">
                    <div class="data-label"><i class="bi bi-people"></i> Jumlah Siswa</div>
                    <p class="data-value" id="detail-siswa">—</p>
                </div>
            </div>
        </div>
         <hr>
         <div class="data-group">
             <div class="data-label"><i class="bi bi-flag"></i> Status Pipeline</div>
             <div class="d-flex align-items-center gap-2">
                 <span class="badge-stage" id="detail-stage">—</span>
             </div>
         </div>
        <div class="data-group">
            <div class="data-label"><i class="bi bi-file-earmark-text"></i> Tindak Lanjut</div>
            <p class="data-value" id="detail-tindak">—</p>
        </div>
        <div class="data-group">
            <div class="data-label"><i class="bi bi-journal-text"></i> Catatan</div>
            <p class="data-value" id="detail-catatan">—</p>
        </div>
        <hr>
        <div class="data-group small text-muted">
            <div class="row">
                <div class="col-6">
                    <div>Dibuat pada</div>
                    <strong id="detail-created">—</strong>
                </div>
                <div class="col-6">
                    <div>Update terakhir</div>
                    <strong id="detail-updated">—</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer p-3">
        <div class="d-grid gap-2">
            <a id="detail-btn-edit" href="#" class="btn btn-primary">
                <i class="bi bi-pencil-square me-2"></i> Edit Data Lengkap
            </a>
            <a id="detail-btn-aktivitas" href="#" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-2"></i> Lihat Aktivitas
            </a>
            <a id="detail-btn-progress" href="#" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up-arrow me-2"></i> Lihat Progress
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const schoolDetailOffcanvas = document.getElementById('schoolDetail');
    if (schoolDetailOffcanvas) {
        schoolDetailOffcanvas.addEventListener('show.bs.offcanvas', function (event) {
            const button = event.relatedTarget;
            const data = button.dataset;
            const offcanvas = this;

            offcanvas.querySelector('.offcanvas-title').textContent = data.nama || 'Detail Sekolah';

            // Mengisi data ke dalam offcanvas
            Object.keys(data).forEach(key => {
                const el = offcanvas.querySelector(`#detail-${key}`);
                if (el) {
                    if (key === 'stage') {
                        // Khusus untuk stage, kita juga update class badge
                        const stageBadge = offcanvas.querySelector('#detail-stage');
                        // Reset class
                        stageBadge.className = 'badge-stage';
                        // Tambah class baru berdasarkan stage
                        if(data.stage === 'Calon') stageBadge.classList.add('calon');
                        else if(data.stage === 'Sudah Dihubungi') stageBadge.classList.add('shb');
                        else if(data.stage === 'Sudah Dilatih') stageBadge.classList.add('slth');
                        else if(data.stage === 'MOU Aktif') stageBadge.classList.add('mou');
                        else if(data.stage === 'Tindak Lanjut MOU') stageBadge.classList.add('tlmou');
                        else if(data.stage === 'Ditolak') stageBadge.classList.add('tolak');
                        else stageBadge.classList.add('secondary');

                        stageBadge.textContent = data.stage;

                    } else {
                         el.textContent = data[key] || '—';
                    }
                }
            });

            // Mengatur link action
            offcanvas.querySelector('#detail-btn-edit').href = data.edit || '#';
            offcanvas.querySelector('#detail-btn-aktivitas').href = data.aktivitas || '#';
            offcanvas.querySelector('#detail-btn-progress').href = data.progress || '#';

            const batchBtn = offcanvas.querySelector('#detail-btn-batch');
            if(batchBtn) {
                if (data.batch) {
                    batchBtn.href = data.batch;
                    batchBtn.style.display = 'inline-flex';
                } else {
                    batchBtn.style.display = 'none';
                }
            }
        });
    }
});
</script>
@endpush