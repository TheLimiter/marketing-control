@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    use App\Models\BillingPaymentFile;

    $badge = [
        'modul_progress' => 'info',
        'modul_done'     => 'success',
        'modul_reopen'   => 'warning',
        'modul_attach'   => 'secondary',
        'stage_change'   => 'dark',
        'kunjungan'      => 'primary',
        'meeting'        => 'secondary',
        'follow_up'      => 'secondary',
        'whatsapp'       => 'success',
        'email'          => 'secondary',
        'lainnya'        => 'light',
        'billing_payment' => 'success',
        'billing_create'  => 'secondary',
    ];
    $toggle = request('dir', 'desc') === 'desc' ? 'asc' : 'desc';
    $currentSort = request('sort', 'tanggal');
@endphp

@push('styles')
<style>
/* =========================================================
    Static Width Table Layout for Aktivitas
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

/* Penentuan Lebar Kolom (wajib ada) */
.col-check      { width: 40px; text-align: center; }
.col-tanggal    { width: 160px; }
.col-sekolah    { width: 250px; }
.col-jenis      { width: 140px; }
.col-hasil      { width: 240px; }
.col-catatan    { width: 240px; }
.col-oleh       { width: 150px; }
.col-aksi       { width: 100px; text-align: center; }


/* Utilitas untuk Ellipsis (...) */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

/* Badge styling */
.badge-stage{ border-radius:9999px; font-size:12px; padding:.3rem .55rem; border:1px solid transparent; font-weight:600; display:inline-block; }
.badge-stage.calon{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
.badge-stage.shb{ background:#ecfeff; color:#155e75; border-color:#a5f3fc; }
.badge-stage.slth{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.mou{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.tlmou{ background:#fefce8; color:#854d0e; border-color:#fde68a; }
.badge-stage.tolak{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.badge-stage.info{ background:#ecfeff; color:#0f766e; border-color:#a7f3d0; }
.badge-stage.success{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.warning{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
.badge-stage.secondary{ background:#f3f4f6; color:#111827; border-color:#e5e7eb; }
.badge-stage.dark{ background:#eef2ff; color:#0f172a; border-color:#d1d5db; }
.badge-stage.primary{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.light{ background:#ffffff; color:#374151; border-color:#f3f4f6; }

/* Tambahan: Gaya untuk Lampiran */
.attachments details{ display:inline-block; position: relative; }
.attachments summary{ list-style:none; cursor:pointer; }
.attachments summary::-webkit-details-marker{ display:none; }
.badge-clip{
    display:inline-flex; align-items:center; gap:.35rem;
    border-radius:999px; padding:.15rem .45rem; font-weight:600; font-size:.75rem;
    background:#e9f2ff; color:#1d4ed8; border:1px solid #cfe0ff;
    cursor:pointer;
}
.attachment-list {
    position: absolute;
    z-index: 10;
    background:#fff;
    border:1px solid var(--border-color);
    border-radius:8px;
    padding:.5rem .75rem;
    box-shadow:0 10px 24px rgba(17,24,39,.08);
    min-width:220px;
    margin-top: 5px;
}


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
            <div class="h-page">Semua Aktivitas</div>
            <div class="subtle">Riwayat aktivitas dari seluruh sekolah</div>
        </div>
    </div>

    {{-- Filter Toolbar (dipertahankan) --}}
    <form method="get" id="filterForm" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="d-flex gap-2 align-items-end">
                <div class="field" style="min-width:140px">
                    <label>Dari</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="input-soft">
                </div>
                <div class="field" style="min-width:140px">
                    <label>Sampai</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="input-soft">
                </div>
            </div>
            <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari Sekolah</label>
                <input name="school" value="{{ request('school') }}" class="input-soft" placeholder="Nama sekolah">
            </div>
            <div class="field" style="min-width:180px">
                <label>Jenis</label>
                <input type="text" name="jenis" value="{{ request('jenis') }}" class="input-soft" placeholder="Ketik jenis">
            </div>
            <div class="field flex-grow-1" style="min-width:220px">
                <label>Cari (hasil/catatan)</label>
                <input name="q" value="{{ request('q') }}" class="input-soft" placeholder="Kata kunci">
            </div>
            <div class="field" style="min-width:200px">
                <label>Oleh</label>
                <input type="text" name="oleh" value="{{ request('oleh') }}" class="input-soft" placeholder="Nama user" list="dl-oleh">
                @isset($creatorOptions)
                    <datalist id="dl-oleh">
                        @foreach($creatorOptions as $n)
                            <option value="{{ $n }}"></option>
                        @endforeach
                    </datalist>
                @endisset
            </div>
            <div class="field" style="min-width:120px">
                <label>Per halaman</label>
                <select name="per" class="select-soft" onchange="this.form.submit()">
                    @foreach([15,25,50,100] as $n)
                        <option value="{{ $n }}" @selected(request('per',25)==$n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ms-auto d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary round">
                    <i class="bi bi-filter me-1"></i> Terapkan
                </button>
                <a href="{{ route('aktivitas.index') }}" class="btn btn-ghost round">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>
        <div class="toolbar-footer d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">Hari ini</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(7)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">7 hari</a>
            <a href="{{ request()->fullUrlWithQuery(['from'=>now()->subDays(30)->toDateString(),'to'=>now()->toDateString()]) }}" class="btn btn-sm btn-ghost round">30 hari</a>
            <a href="{{ route('aktivitas.export', request()->all()) }}" class="btn btn-sm btn-outline-success round ms-auto">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            @php $isTrash = request()->boolean('trashed'); @endphp
            @if($isTrash)
                <a href="{{ request()->fullUrlWithoutQuery(['trashed','with_trashed','page']) }}"
                   class="btn btn-ghost round">
                    <i class="bi bi-arrow-left me-1"></i> Keluar dari Sampah
                </a>
                <a href="{{ route('aktivitas.index', array_merge(request()->except(['trashed','page']), ['with_trashed'=>1])) }}"
                   class="btn btn-outline-secondary round">
                    Semua (+ terhapus)
                </a>
            @else
                <a href="{{ route('aktivitas.index', array_merge(request()->except(['with_trashed','page']), ['trashed'=>1])) }}"
                   class="btn btn-outline-danger round">
                    <i class="bi bi-trash3 me-1"></i> Lihat yang terhapus
                </a>
            @endif
        </div>
    </form>

    {{-- Tabel (Struktur Baru) --}}
    <div class="card p-0">
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" id="chkAll" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)"></th>
                        <th class="col-tanggal"><a href="{{ request()->fullUrlWithQuery(['sort'=>'tanggal','dir'=>$toggle]) }}" class="text-decoration-none text-reset">Tanggal</a></th>
                        <th class="col-sekolah">Sekolah</th>
                        <th class="col-jenis"><a href="{{ request()->fullUrlWithQuery(['sort'=>'jenis','dir'=>$toggle]) }}" class="text-decoration-none text-reset">Jenis</a></th>
                        <th class="col-hasil">Hasil</th>
                        <th class="col-catatan">Catatan</th>
                        <th class="col-oleh"><a href="{{ request()->fullUrlWithQuery(['sort'=>'creator_name','dir'=>$toggle]) }}" class="text-decoration-none text-reset">Oleh</a></th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $it)
                        @php $k = strtolower($it->jenis ?? 'lainnya'); @endphp
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" name="ids[]" value="{{ $it->id }}" class="rowchk" form="bulkForm">
                            </td>
                            <td class="col-tanggal">
                                <div class="fw-semibold ellipsis-wrapper">{{ optional($it->tanggal ?? $it->created_at)->format('d M Y, H:i') ?: '-' }}</div>
                                <div class="small-muted ellipsis-wrapper">{{ optional($it->created_at ?? $it->tanggal)->diffForHumans() }}</div>
                            </td>
                            <td class="col-sekolah">
                                <a href="{{ route('master.aktivitas.index', optional($it->master)->id) }}" class="text-decoration-none ellipsis-wrapper fw-semibold">{{ optional($it->master)->nama_sekolah ?? '-' }}</a>
                                <div class="small-muted ellipsis-wrapper">{{ optional($it->master)->kota_kab ?? ' ' }}</div>
                            </td>
                            <td class="col-jenis">
                                <span class="badge-stage {{ $badge[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span>
                            </td>
                            <td class="col-hasil">
                                <div class="ellipsis-wrapper">{{ $it->hasil }}</div>

                                {{-- START: Lampiran Aktivitas dan Pembayaran --}}
                                @php
                                    $activityFiles = $it->files ?? collect();
                                    $paymentFiles = $it->paymentFiles ?? collect();
                                    $fc = $activityFiles->count() + $paymentFiles->count();
                                @endphp

                                @if($fc)
                                    <span class="attachments d-inline-block mt-1" style="position: relative;">
                                        <details>
                                            <summary class="badge-clip" title="{{ $fc }} lampiran aktivitas">
                                                <i class="bi bi-paperclip"></i> {{ $fc }}
                                            </summary>
                                            <ul class="list-unstyled small mb-0 p-2 attachment-list">
                                                {{-- Lampiran aktivitas biasa --}}
                                                @foreach($activityFiles as $f)
                                                    <li class="d-flex align-items-center gap-2 mb-1">
                                                        <div class="ellipsis-wrapper" style="max-width: 140px;">
                                                            <a href="{{ route('aktivitas.file.download', $f->id) }}" class="text-decoration-none">{{ $f->original_name }}</a>
                                                        </div>
                                                        <span class="text-muted" style="font-size: 0.8em; white-space: nowrap;">({{ number_format(($f->size ?? 0)/1024,1) }} KB)</span>

                                                        @php
                                                            $mime = $f->mime ?? '';
                                                        @endphp
                                                        @if(Str::startsWith($mime, ['image/', 'application/pdf']))
                                                            <button type="button" class="btn btn-sm btn-ghost round" style="font-size:.75rem; padding:.25rem .4rem; line-height:1;"
                                                                onclick="previewFile(@json(route('aktivitas.file.preview', $f->id)), @json($f->original_name), @json($mime))" title="Pratinjau">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        @endif
                                                    </li>
                                                @endforeach

                                                {{-- Bukti pembayaran --}}
                                                @foreach($paymentFiles as $bf)
                                                    <li class="d-flex align-items-center gap-2 mb-1">
                                                        <div class="ellipsis-wrapper" style="max-width: 140px;">
                                                            <a href="{{ asset('storage/'.$bf->path) }}" target="_blank" class="text-decoration-none">{{ $bf->original_name }}</a>
                                                        </div>
                                                        <span class="text-muted" style="font-size: 0.8em; white-space: nowrap;">({{ number_format(($bf->size ?? 0)/1024,1) }} KB)</span>

                                                        @php
                                                            $bfMime = $bf->mime ?? '';
                                                        @endphp
                                                        @if(Str::startsWith($bfMime, ['image/', 'application/pdf']))
                                                            <button type="button" class="btn btn-sm btn-ghost round" style="font-size:.75rem; padding:.25rem .4rem; line-height:1;"
                                                                onclick="previewFile(@json(asset('storage/'.$bf->path)), @json($bf->original_name), @json($bfMime))" title="Pratinjau">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </span>
                                @endif
                                {{-- END: Lampiran Aktivitas dan Pembayaran --}}

                                {{-- START: Lampiran MOU dari MasterSekolah --}}
                                @if(optional($it->master)->mou_path)
                                    <span class="attachments d-inline-block mt-1 ms-2" style="position: relative;">
                                        <details>
                                            <summary class="badge-clip" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;" title="MOU tersedia">
                                                <i class="bi bi-file-earmark-check"></i> MOU
                                            </summary>
                                            <ul class="list-unstyled small mb-0 p-2 attachment-list">
                                                <li class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="ellipsis-wrapper" style="max-width: 140px;">
                                                        {{-- Menggunakan route 'master.mou.download' yang ada di MouController --}}
                                                        <a href="{{ route('master.mou.download', $it->master->id) }}" class="text-decoration-none">Dokumen MOU</a>
                                                    </div>

                                                    @php
                                                        // Menggunakan route 'master.mou.preview' yang ada di MouController
                                                        $mouUrl = route('master.mou.preview', $it->master->id);

                                                        $mouPath = optional($it->master)->mou_path;
                                                        // Asumsi: Kita harus mencoba menebak MIME dari ekstensi path karena tidak disimpan di DB.
                                                        if (Str::endsWith(strtolower((string)$mouPath), ['.pdf'])) {
                                                            $mouMime = 'application/pdf';
                                                        } elseif (Str::endsWith(strtolower((string)$mouPath), ['.jpg', '.jpeg', '.png', '.webp'])) {
                                                            $mouMime = 'image/jpeg';
                                                        } else {
                                                            $mouMime = 'application/octet-stream';
                                                        }

                                                        $mouName = 'MOU-' . Str::slug(optional($it->master)->nama_sekolah);
                                                    @endphp
                                                    <button type="button" class="btn btn-sm btn-ghost round" style="font-size:.75rem; padding:.25rem .4rem; line-height:1;"
                                                        onclick="previewFile(@json($mouUrl), @json($mouName), @json($mouMime))" title="Pratinjau MOU">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </li>
                                                @if(optional($it->master)->ttd_status)
                                                    <li class="text-success fw-medium mt-1">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Sudah ditandatangani
                                                    </li>
                                                @else
                                                    <li class="text-warning fw-medium mt-1">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Belum ditandatangani
                                                    </li>
                                                @endif
                                            </ul>
                                        </details>
                                    </span>
                                @endif
                                {{-- END: Lampiran MOU --}}

                            </td>
                            <td class="col-catatan">
                                <div class="ellipsis-wrapper small-muted">{!! $it->catatan ? nl2br(e(Str::limit($it->catatan,100))) : '-' !!}</div>
                            </td>
                            <td class="col-oleh">
                                <div class="ellipsis-wrapper">{{ optional($it->creator)->name ?? '-' }}</div>
                            </td>
                            <td class="col-aksi">
                                @if(optional($it->master)->id)
                                    @if(method_exists($it,'trashed') && $it->trashed())
                                        {{-- Aksi untuk item yang terhapus --}}
                                    @else
                                        <a class="btn btn-sm btn-outline-secondary round" href="{{ route('master.aktivitas.index', $it->master->id) }}">Detail</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state-cell">
                                <div class="fs-4 mb-2">📑</div>
                                <div class="fw-semibold">Belum ada aktivitas</div>
                                <div class="small-muted">Gunakan filter untuk mencari data.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bulk Actions & Pagination Footer --}}
        <div class="card-footer">
            <form id="bulkForm" method="post" action="{{ route('aktivitas.bulk') }}" class="d-flex flex-wrap gap-3 align-items-center">
                @csrf
                <div class="d-flex gap-2 align-items-center">
                    <select name="action" class="select-soft" style="width:auto">
                        <option value="" selected>Aksi massal</option>
                        <option value="delete">Hapus terpilih</option>
                        <option value="export">Export CSV terpilih</option>
                    </select>
                    <button class="btn btn-primary round" onclick="return confirm('Lanjutkan aksi massal?')">Jalankan</button>
                </div>
                <div class="ms-auto">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            </form>
        </div>
    </div>

    {{-- File preview modal (sama seperti sebelumnya) --}}
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewTitle">Pratinjau File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="filePreviewContent" class="d-flex justify-content-center align-items-center" style="min-height: 40vh;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
const filePreviewTitle = document.getElementById('filePreviewTitle');
const filePreviewContent = document.getElementById('filePreviewContent');

function previewFile(url, title, mime) {
    filePreviewTitle.textContent = title;
    filePreviewContent.innerHTML = '';

    // MIME type untuk file yang di-preview
    if (mime && (mime.startsWith('image/') || mime === 'application/pdf')) {
        const embed = document.createElement('embed');
        embed.src = url;
        embed.type = mime;
        embed.style.width = '100%';
        embed.style.height = '70vh';
        filePreviewContent.appendChild(embed);
    } else {
        const message = document.createElement('div');
        message.innerHTML = `
            <div class="text-center text-muted">
                <p>Tipe file ini tidak didukung untuk pratinjau. Silakan unduh file untuk melihatnya.</p>
                <a href="${url}" class="btn btn-primary mt-2">Unduh File</a>
            </div>
        `;
        filePreviewContent.appendChild(message);
    }

    filePreviewModal.show();
}
</script>
@endpush

@endsection
