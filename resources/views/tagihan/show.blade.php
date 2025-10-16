@extends('layouts.app')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Storage;

    $isOverdue  = $tagihan->jatuh_tempo && $tagihan->sisa > 0 && Carbon::parse($tagihan->jatuh_tempo)->isPast();
    $isDueToday = $tagihan->jatuh_tempo && $tagihan->sisa > 0 && Carbon::parse($tagihan->jatuh_tempo)->isToday();

    $statusBadge = [
        'draft'    => 'secondary',
        'sebagian' => 'warning',
        'lunas'    => 'success',
    ][$tagihan->status] ?? 'secondary';

    $agingBadge  = $isOverdue ? 'danger' : ($isDueToday ? 'warning' : 'secondary');
    $agingLabel  = $isOverdue
        ? (Carbon::parse($tagihan->jatuh_tempo)->diffInDays(Carbon::today()) . ' hari lewat')
        : ($isDueToday ? 'Jatuh tempo hari ini' : 'Current');
@endphp

@push('styles')
<style>
    .data-group { margin-bottom: 1.25rem; }
    .data-label { font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem; font-weight: 500; }
    .data-value { font-weight: 600; color: #111827; }
    .activity-item { border-bottom: 1px solid #eef2f6; padding: 12px 0; }
    .activity-meta { font-size: 0.85rem; color: #6b7280; }
    .activity-hasil { margin-top: 6px; }
    .attachment-link { font-size: 0.9rem; display:inline-flex; gap:8px; align-items:center; }
</style>
@endpush

@section('content')

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="eyebrow">Detail Tagihan</div>
            <h1 class="h-page mb-0">{{ $tagihan->nomor }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tagihan.edit',$tagihan) }}" class="btn btn-outline-primary round">Edit</a>
            <a href="{{ route('tagihan.index') }}" class="btn btn-ghost round">Kembali</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('ok'))   <div class="alert alert-success">{{ session('ok') }}</div> @endif
    @if(session('err'))  <div class="alert alert-danger">{{ session('err') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        {{-- Kiri --}}
        <div class="col-lg-8">
            {{-- Rincian utama --}}
            <div class="card">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="data-group">
                                <div class="data-label">Sekolah</div>
                                <div class="data-value">{{ $tagihan->sekolah->nama_sekolah ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-group">
                                <div class="data-label">Nomor Tagihan</div>
                                <div class="data-value">{{ $tagihan->nomor ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-group">
                                <div class="data-label">Tanggal Tagihan</div>
                                <div class="data-value">{{ $tagihan->tanggal_tagihan ? date_id($tagihan->tanggal_tagihan) : '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="data-group">
                                <div class="data-label">Jatuh Tempo</div>
                                <div class="data-value">{{ $tagihan->jatuh_tempo ? date_id($tagihan->jatuh_tempo) : '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">
                     <div class="row g-4">
                        <div class="col-md-4">
                            <div class="data-group">
                                <div class="data-label">Total</div>
                                <div class="data-value fs-5">{{ rupiah($tagihan->total) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="data-group">
                                <div class="data-label">Terbayar</div>
                                <div class="data-value fs-5 text-success">{{ rupiah($tagihan->terbayar) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="data-group">
                                <div class="data-label">Sisa</div>
                                <div class="data-value fs-5 {{ $tagihan->sisa > 0 ? 'text-danger' : '' }}">{{ rupiah($tagihan->sisa) }}</div>
                            </div>
                        </div>
                    </div>
                     <hr class="my-3">
                     <div class="data-group mb-0">
                         <div class="data-label">Catatan</div>
                         <p class="data-value mb-0">{{ $tagihan->catatan ?: '-' }}</p>
                     </div>
                </div>
            </div>

            {{-- Modul yang dicakup --}}
            <div class="card mt-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">Modul yang Dicakup</h6>
                    @php $mods = $tagihan->relationLoaded('modul') ? $tagihan->modul : collect(); @endphp
                    @if($mods->isEmpty())
                        <div class="text-muted small">Tidak ada metadata modul untuk tagihan ini.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>#</th><th>Nama Modul</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                @foreach($mods as $i => $m)
                                    <tr>
                                        <td>{{ $i+1 }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $m->nama }}</span></td>
                                        <td class="small-muted">{{ $m->pivot?->keterangan ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIWAYAT AKTIVITAS (BARU): tampilkan hasil + lampiran jika ada --}}
            <div class="card mt-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">Riwayat Aktivitas</h6>

                    @php
                        // Ambil aktivitas: bila tagihan memuat relasi 'aktivitas' gunakan itu,
                        // kalau tidak coba relasi pada sekolah (umumnya aktivitas disimpan per sekolah).
                        $activities = collect();
                        if ($tagihan->relationLoaded('aktivitas')) {
                            $activities = $tagihan->aktivitas;
                        } elseif (isset($tagihan->sekolah) && $tagihan->sekolah->relationLoaded('aktivitas')) {
                            $activities = $tagihan->sekolah->aktivitas;
                        } elseif (isset($tagihan->sekolah) && method_exists($tagihan->sekolah, 'aktivitas')) {
                            // upaya terakhir: lazy load (aman)
                            try {
                                $activities = $tagihan->sekolah->aktivitas;
                            } catch (\Throwable $e) {
                                $activities = collect();
                            }
                        }

                        // Nama-nama kolom properti lampiran yang mungkin digunakan oleh model aktivitas
                        $attachmentKeys = ['lampiran_path','attachment_path','file_path','file','path','dokumen','lampiran'];
                    @endphp

                    @if($activities->isEmpty())
                        <div class="text-muted small">Belum ada aktivitas untuk entitas ini.</div>
                    @else
                        <div class="list-group">
                            @foreach($activities as $act)
                                @php
                                    // tanggal & label
                                    $actDate = $act->tanggal ?? $act->created_at ?? null;
                                    $actDateFmt = $actDate ? \Carbon\Carbon::parse($actDate)->format('d M Y H:i') : '-';
                                    $jenis = $act->jenis ?? ($act->tipe ?? '-');
                                    $hasil = $act->hasil ?? $act->keterangan ?? $act->catatan ?? '-';

                                    // cari properti lampiran yang non-empty
                                    $foundAttachment = null;
                                    $foundAttachmentKey = null;
                                    foreach ($attachmentKeys as $k) {
                                        if (isset($act->$k) && !empty($act->$k)) {
                                            $foundAttachment = $act->$k;
                                            $foundAttachmentKey = $k;
                                            break;
                                        }
                                    }

                                    $attachmentPublicUrl = null;
                                    $attachmentName = null;
                                    if ($foundAttachment) {
                                        // jika path sudah berupa URL
                                        if (preg_match('/^https?:\\/\\//', $foundAttachment)) {
                                            $attachmentPublicUrl = $foundAttachment;
                                            $attachmentName = basename(parse_url($foundAttachment, PHP_URL_PATH) ?: $foundAttachment);
                                        } else {
                                            // asumsi path relatif di disk 'public'
                                            try {
                                                if (Storage::disk('public')->exists($foundAttachment)) {
                                                    $attachmentPublicUrl = Storage::disk('public')->url($foundAttachment);
                                                    $attachmentName = basename($foundAttachment);
                                                } else {
                                                    // kemungkinan sudah tersimpan tetapi di disk lain / tidak public
                                                    $attachmentPublicUrl = null;
                                                }
                                            } catch (\Throwable $e) {
                                                $attachmentPublicUrl = null;
                                            }
                                        }
                                    }
                                @endphp

                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="activity-meta">
                                                <strong>{{ ucfirst($jenis) }}</strong>
                                                &middot;
                                                <span class="ms-1">{{ $actDateFmt }}</span>
                                            </div>
                                            <div class="activity-hasil mt-1">{!! nl2br(e($hasil)) !!}</div>
                                        </div>

                                        <div class="text-end">
                                            @if($attachmentPublicUrl)
                                                <div class="mb-1">
                                                    <a class="attachment-link" href="{{ $attachmentPublicUrl }}" target="_blank" rel="noopener" download>
                                                        <i class="bi bi-paperclip"></i>
                                                        <span>{{ $attachmentName }}</span>
                                                    </a>
                                                </div>
                                            @else
                                                @if($foundAttachment)
                                                    <div class="small text-muted">Lampiran terdaftar ({{ $foundAttachmentKey }}) tetapi tidak ditemukan di storage.</div>
                                                @endif
                                            @endif

                                            @if(method_exists($act,'created_by') && $act->created_by)
                                                <div class="small text-muted">Oleh: {{ $act->created_by }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Kanan: Aksi --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4 form--soft">
                    <h6 class="mb-3">Catat Pembayaran</h6>
                    <form action="{{ route('tagihan.bayar.simpan', $tagihan) }}" method="post" enctype="multipart/form-data" class="vstack gap-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Nominal</label>
                                <input type="text" id="nominal_display" class="form-control input-soft" placeholder="0" required>
                                <input type="hidden" name="nominal" id="nominal_hidden">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tgl Bayar</label>
                                <input type="date" name="tanggal_bayar" class="form-control input-soft" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div>
                             <label class="form-label">Metode</label>
                            <input type="text" name="metode" class="form-control input-soft" placeholder="e.g., Transfer Bank">
                        </div>
                        <div>
                             <label class="form-label">Catatan</label>
                             <input type="text" name="catatan" class="form-control input-soft" placeholder="Opsional">
                        </div>
                        <div>
                            <label class="form-label">Bukti Transaksi</label>
                            <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf">
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button class="btn btn-success round flex-grow-1">Simpan Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fungsi format Rupiah untuk input
    function setupCurrencyInput(displayId, hiddenId) {
        const displayInput = document.getElementById(displayId);
        const hiddenInput = document.getElementById(hiddenId);
        if (!displayInput || !hiddenInput) return;

        displayInput.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/[^0-9]/g, '');
            hiddenInput.value = rawValue;
            if (rawValue) {
                e.target.value = parseInt(rawValue, 10).toLocaleString('id-ID');
            } else {
                e.target.value = '';
            }
        });
    }
    setupCurrencyInput('nominal_display', 'nominal_hidden');
});
</script>
@endpush
