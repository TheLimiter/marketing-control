@extends('layouts.app')

@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use App\Models\BillingPaymentFile;

    // --- Data Pipeline ---
    $calon       = (int) Arr::get($counts,'calon',0);
    $shb         = (int) Arr::get($counts,'sudah_dihubungi',0);
    $slth        = (int) Arr::get($counts,'sudah_dilatih',0);
    $mou         = (int) Arr::get($counts,'mou_aktif',0);
    $tlmou       = (int) Arr::get($counts,'tindak_lanjut_mou',0);
    $tolak       = (int) Arr::get($counts,'ditolak',0);
    $mouNoFile   = (int) ($mouTanpaFile ?? 0);

    // --- Data Aktivitas & Badge ---
    $badge = [
        'modul_progress' => 'info', 'modul_done' => 'success', 'modul_reopen' => 'warning',
        'modul_attach' => 'secondary', 'stage_change' => 'dark', 'kunjungan' => 'primary',
        'meeting' => 'secondary', 'follow_up' => 'secondary', 'whatsapp' => 'success',
        'email' => 'secondary', 'lainnya' => 'light', 'billing_payment' => 'success',
        'billing_create' => 'secondary',
    ];

    $jenisDesc = [
        'modul_progress' => 'Progres penggunaan modul',
        'modul_done'     => 'Modul selesai digunakan',
        'modul_reopen'   => 'Modul dibuka ulang / diaktifkan kembali',
        'modul_attach'   => 'Lampiran/berkas terkait modul',
        'stage_change'   => 'Perubahan/kenaikan stage/tahap sekolah (indikator pipeline)',
        'kunjungan'      => 'Kunjungan langsung ke sekolah',
        'meeting'        => 'Pertemuan tatap muka atau online',
        'follow_up'      => 'Tindak lanjut dari komunikasi sebelumnya',
        'whatsapp'       => 'Komunikasi melalui WhatsApp',
        'email'          => 'Komunikasi melalui email',
        'lainnya'        => 'Aktivitas lain di luar kategori di atas',
        'billing_payment'=> 'Pembayaran tagihan diterima',
        'billing_create' => 'Tagihan baru dibuat',
    ];
@endphp

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout for Dashboard
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
.static-table thead th {
    border-bottom: 2px solid var(--border-color);
}


.static-table tbody tr:last-child td {
    border-bottom: none;
}

.static-table tbody tr:hover {
    background-color: var(--row-hover-bg);
}

/* Penentuan Lebar Kolom */
.col-tanggal { width: 150px; }
.col-sekolah { width: 250px; }
.col-jenis   { width: 150px; }
.col-hasil   { width: 250px; }
.col-catatan { width: 250px; }
.col-oleh    { width: 150px; }

/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

/* Badge stage (disalin agar self-contained) */
.badge-stage{ border-radius:9999px; font-size:12px; padding:.3rem .55rem; border:1px solid transparent; font-weight:600; display:inline-block; }
.badge-stage.info{ background:#ecfeff; color:#0f766e; border-color:#a7f3d0; }
.badge-stage.success{ background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
.badge-stage.warning{ background:#fffbeb; color:#92400e; border-color:#fde68a; }
.badge-stage.secondary{ background:#f3f4f6; color:#111827; border-color:#e5e7eb; }
.badge-stage.dark{ background:#eef2ff; color:#0f172a; border-color:#d1d5db; }
.badge-stage.primary{ background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
.badge-stage.light{ background:#ffffff; color:#374151; border-color:#f3f4f6; }

/* Empty state */
.empty-state-cell {
    text-align: center;
    padding: 40px;
}
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="h-hero">Dashboard</div>
            <div class="subtle">{{ now()->translatedFormat('l, d F Y') }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('master.create') }}" class="btn btn-primary round"><i class="bi bi-plus-lg me-1"></i> Tambah Sekolah</a>
            @if(Route::has('tagihan.create'))
            <a href="{{ route('tagihan.create') }}" class="btn btn-outline-primary round"><i class="bi bi-receipt me-1"></i> Buat Tagihan</a>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('ok'))<div class="alert alert-success elev-1">{{ session('ok') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger elev-1">{{ session('error') }}</div>@endif

    {{-- KPI Pipeline --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="eyebrow">Calon</div><div class="h4 mb-0">{{ number_format($calon) }}</div></div></div></div>
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="eyebrow">Sudah Dihubungi</div><div class="h4 mb-0">{{ number_format($shb) }}</div></div></div></div>
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="eyebrow">Sudah Dilatih</div><div class="h4 mb-0">{{ number_format($slth) }}</div></div></div></div>
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="eyebrow">MOU Aktif</div><div class="h4 mb-0">{{ number_format($mou) }}</div></div></div></div>
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-3"><div class="eyebrow">Tindak Lanjut MOU</div><div class="h4 mb-0">{{ number_format($tlmou) }}</div></div></div></div>
        <div class="col-6 col-md-2"><div class="card border-danger"><div class="card-body py-3"><div class="eyebrow text-danger">Ditolak</div><div class="h4 mb-0 text-danger">{{ number_format($tolak) }}</div></div></div></div>
    </div>

    {{-- Indikator Progress Modul --}}
    <div class="card card-toolbar mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="h-section mb-0">
                <i class="bi bi-graph-up-arrow text-muted"></i>
                <span>Progress Modul</span>
            </div>
            <a href="{{ route('progress.index') }}" class="link-action">
                Lihat Detail <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="hr-soft my-3"></div>
        <div class="row g-3">
            <div class="col-4"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Sekolah Dilatih</div><div class="h4 mb-0">{{ number_format($progressCounts['dilatih'] ?? 0) }}</div></div></div></div>
            <div class="col-4"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Sekolah Didampingi</div><div class="h4 mb-0">{{ number_format($progressCounts['didampingi'] ?? 0) }}</div></div></div></div>
            <div class="col-4"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Sekolah Mandiri</div><div class="h4 mb-0">{{ number_format($progressCounts['mandiri'] ?? 0) }}</div></div></div></div>
        </div>
    </div>

    {{-- Statistik Tagihan --}}
    <div class="card card-toolbar mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="h-section mb-0">
                <i class="bi bi-wallet2 text-muted"></i>
                <span>Statistik Tagihan</span>
            </div>
            @if(Route::has('tagihan.index'))
                <a href="{{ route('tagihan.index') }}" class="link-action">
                    Kelola Tagihan <i class="bi bi-arrow-right"></i>
                </a>
            @endif
        </div>
        <div class="hr-soft my-3"></div>
        <div class="row g-3">
            <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Total Tagihan</div><div class="h4 mb-0">{{ number_format($billingStats['total'] ?? 0) }}</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Total Nominal</div><div class="h4 mb-0">Rp {{ number_format($billingStats['amount'] ?? 0, 0, ',', '.') }}</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Total Terbayar</div><div class="h4 mb-0 text-success">Rp {{ number_format($billingStats['paid'] ?? 0, 0, ',', '.') }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body py-3"><div class="eyebrow">Collection Rate</div><div class="h4 mb-0 text-success">{{ $billingStats['collection_rate'] ?? 0 }}%</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-danger h-100"><div class="card-body py-3"><div class="eyebrow text-danger">Overdue</div><div class="h4 mb-0 text-danger">{{ number_format($billingStats['overdue'] ?? 0) }}</div></div></div></div>
        </div>
        @if(!empty($topOverdue) && $topOverdue->count())
            <div class="hr-soft my-3"></div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small fw-bold text-muted">Top {{ $topOverdue->count() }} Tagihan Overdue (Paling Lama)</div>
                <a href="{{ route('tagihan.index', ['only_overdue'=>1]) }}" class="link-action small">Lihat semua</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach($topOverdue as $t)
                    <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 px-1">
                        <div>
                            <div class="fw-semibold">{{ $t->nomor }}</div>
                            <div class="small text-muted">{{ optional($t->sekolah)->nama_sekolah ?? '-' }}</div>
                            <div class="small text-danger">Jatuh tempo: {{ optional($t->jatuh_tempo)->format('d M Y') }} ({{ optional($t->jatuh_tempo)->diffForHumans(null, true) }} lalu)</div>
                            <div class="small text-muted">Sisa: Rp {{ number_format($t->total - $t->terbayar,0,',','.') }}</div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('tagihan.show', $t) }}" class="btn btn-sm btn-outline-secondary round">Detail</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recent activities --}}
    <div class="card p-0">
        <div class="card-header border-0 bg-transparent p-3 d-flex justify-content-between align-items-center">
            <div class="h-section mb-0">
                <i class="bi bi-clock-history text-muted"></i>
                <span>Aktivitas Terbaru</span>
            </div>
            @if(Route::has('aktivitas.index'))
            <a href="{{ route('aktivitas.index') }}" class="link-action">
                Semua Aktivitas
                <i class="bi bi-arrow-right"></i>
            </a>
            @endif
        </div>
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-tanggal">Tanggal</th>
                        <th class="col-sekolah">Sekolah</th>
                        <th class="col-jenis">Jenis</th>
                        <th class="col-hasil">Hasil</th>
                        <th class="col-catatan">Catatan</th>
                        <th class="col-oleh">Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $it)
                        @php $k = strtolower($it->jenis ?? 'lainnya'); @endphp
                        <tr>
                            <td class="col-tanggal">
                                <div class="fw-semibold ellipsis-wrapper">{{ optional($it->tanggal ?? $it->created_at)->format('d M Y') ?: '-' }}</div>
                                <div class="small-muted ellipsis-wrapper">{{ optional($it->created_at ?? $it->tanggal)->diffForHumans() }}</div>
                            </td>
                            <td class="col-sekolah">
                                <div class="fw-semibold ellipsis-wrapper">{{ optional($it->master)->nama_sekolah ?? '-' }}</div>
                            </td>
                            <td class="col-jenis">
                                <span class="badge-stage {{ $badge[$k] ?? 'secondary' }}">{{ strtoupper($it->jenis ?? 'LAINNYA') }}</span>
                            </td>
                            <td class="col-hasil">
                                <div class="ellipsis-wrapper">{{ $it->hasil }}</div>
                                @php
                                    $allFiles = collect()->concat($it->files ?? [])->concat($it->paymentFiles ?? []);
                                    $fc = $allFiles->count();
                                @endphp
                                @if($fc)
                                  <details class="d-inline-block align-middle">
                                      <summary class="badge rounded-pill text-bg-secondary border-0" style="cursor:pointer; list-style:none; display:inline-block;">
                                          <i class="bi bi-paperclip me-1"></i> {{ $fc }}
                                      </summary>
                                      <ul class="list-unstyled small mb-0 mt-1">
                                          @foreach($allFiles as $f)
                                              @php
                                                  $isBilling   = $f instanceof BillingPaymentFile;
                                                  $previewUrl  = $isBilling
                                                      ? route('billing.file.preview',  $f->id)
                                                      : route('aktivitas.file.preview', $f->id);
                                                  $downloadUrl = $isBilling
                                                      ? route('billing.file.download', $f->id)
                                                      : route('aktivitas.file.download', $f->id);
                                              @endphp
                                              <li class="d-flex align-items-center gap-2">
                                                  <a href="{{ $downloadUrl }}" class="text-truncate">{{ $f->original_name }}</a>
                                                  <span class="text-muted text-nowrap">({{ number_format(($f->size ?? 0)/1024,1) }} KB)</span>
                                                  <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                          onclick="previewFile(@json($previewUrl), @json($f->original_name), @json($f->mime))">
                                                      <i class="bi bi-eye"></i>
                                                  </button>
                                              </li>
                                          @endforeach
                                      </ul>
                                  </details>
                                @endif
                            </td>
                            <td class="col-catatan">
                                <div class="small-muted ellipsis-wrapper" title="{{ $it->catatan }}">
                                    {!! $it->catatan ? nl2br(e(Str::limit($it->catatan,120))) : '-' !!}
                                </div>
                            </td>
                            <td class="col-oleh">
                                <div class="small-muted ellipsis-wrapper">{{ $it->creator_name ?? optional($it->creator)->name ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state-cell">
                                Belum ada aktivitas terbaru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legend / Keterangan Jenis Aktivitas --}}
    <div class="card mt-4">
       <div class="card-body">
            <div class="h-section mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-muted"></i>
                <span>Keterangan Jenis Aktivitas</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px">Jenis</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="small text-muted">
                        @foreach($badge as $jenis => $color)
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge-stage {{ $color }}">{{ $jenis }}</span>
                                </td>
                                <td>{{ $jenisDesc[$jenis] ?? 'Tidak ada deskripsi' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- File preview modal --}}
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
@endsection

@push('scripts')
<script>
 const filePreviewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
 const filePreviewTitle = document.getElementById('filePreviewTitle');
 const filePreviewContent = document.getElementById('filePreviewContent');

 function previewFile(url, title, mime) {
   filePreviewTitle.textContent = title;
   filePreviewContent.innerHTML = '';

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
