@extends('layouts.app')

@php
$lapParams = array_filter([
    'master_sekolah_id' => request('master_sekolah_id'),
    'status'            => request('status'),
    'date_from'         => request('dari'),
    'date_to'           => request('sampai'),
    'q'                 => request('q'),
    'due_only'          => request('only_due') ? 1 : null,
], fn($v) => $v !== null && $v !== '');

$per = (int) request('per', 25);
@endphp

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout
   - Menggunakan display:table dan table-layout:fixed
   - Lebar kolom statis, konten panjang akan terpotong (...)
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
    overflow-x: auto; /* Agar responsif di layar kecil */
    background-color: #fff;
}

.static-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed; /* KUNCI UTAMA: Memaksa lebar kolom statis */
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
.col-klien { width: 240px; }
.col-nomor { width: 180px; }
.col-tgl-tagih { width: 120px; }
.col-jatuh-tempo { width: 140px; }
.col-jumlah, .col-bayar, .col-sisa { width: 120px; text-align: right; }
.col-status { width: 110px; }
.col-aksi { width: 100px; text-align: center; }


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
.badge-stage {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}
/* Anda bisa tambahkan warna badge di sini jika belum ada di file css utama */

/* Empty state */
.empty-state-cell {
    text-align: center;
    padding: 40px;
}

</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="eyebrow">Data</div>
        <h1 class="h-page mb-0">Tagihan Klien</h1>
        <div class="subtle">Kelola dan pantau tagihan klien</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('tagihan.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Buat Tagihan
        </a>
        <a href="{{ route('tagihan.laporan', $lapParams) }}" class="btn btn-success round">Laporan</a>
        <a href="{{ route('tagihan.laporan.csv', $lapParams) }}" class="btn btn-outline-success round">Export CSV</a>
    </div>
</div>

{{-- Filter toolbar --}}
<form method="get" class="card card-toolbar mb-3">
    <div class="toolbar">
        <div class="field" style="min-width:180px">
            <label>Klien</label>
            <select name="master_sekolah_id" class="select-soft">
                <option value="">Semua</option>
                @foreach($sekolah as $s)
                    <option value="{{ $s->id }}" @selected((string)(request('master_sekolah_id') ?? '') === (string)$s->id)>
                        {{ $s->nama_sekolah }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field" style="min-width:120px">
            <label>Status</label>
            <select name="status" class="select-soft">
                <option value="">Semua</option>
                @foreach(['lunas','sebagian','draft','overdue'] as $st)
                    <option value="{{ $st }}" @selected((request('status') ?? '') === $st)>{{ ucwords($st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="min-width:120px">
            <label>Dari</label>
            <input type="date" name="dari" value="{{ request('dari') }}" class="input-soft">
        </div>
        <div class="field" style="min-width:120px">
            <label>Sampai</label>
            <input type="date" name="sampai" value="{{ request('sampai') }}" class="input-soft">
        </div>
        <div class="field flex-grow-1" style="min-width:180px">
            <label>Cari</label>
            <input type="text" name="q" value="{{ request('q') }}" class="input-soft" placeholder="keterangan / nomor">
        </div>
        <div class="field" style="min-width:100px">
            <label>Per</label>
            <select name="per" class="select-soft" onchange="this.form.submit()">
                @foreach([15,25,50,100] as $n)
                    <option value="{{ $n }}" @selected($per === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-auto d-flex align-items-end">
            <button class="btn btn-primary round">Terapkan</button>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
        <a href="{{ url()->current() }}" class="btn btn-ghost round">Reset</a>
        <a href="{{ request()->fullUrlWithQuery(['dari'=>now()->toDateString(),'sampai'=>now()->toDateString()]) }}" class="btn btn-ghost round">Hari ini</a>
        <a href="{{ request()->fullUrlWithQuery(['dari'=>now()->subDays(7)->toDateString(),'sampai'=>now()->toDateString()]) }}" class="btn btn-ghost round">7 hari</a>
        <div class="form-check d-flex align-items-center me-2">
            <input type="checkbox" class="form-check-input" id="only_due" name="only_due" {{ request('only_due') ? 'checked' : '' }}>
            <label class="form-check-label ms-2" for="only_due">Due (hari ini)</label>
        </div>
        <div class="form-check d-flex align-items-center">
            <input type="checkbox" class="form-check-input" id="only_overdue" name="only_overdue" {{ request('only_overdue') ? 'checked' : '' }}>
            <label class="form-check-label ms-2" for="only_overdue">Overdue</label>
        </div>
        <div class="ms-auto">
             <a href="{{ route('tagihan.trash') }}" class="btn btn-outline-danger round">
                <i class="bi bi-trash3 me-1"></i> Lihat Data Terhapus
            </a>
        </div>
    </div>
</form>

<div class="card p-0">
    <div class="table-container">
        <table class="static-table">
            <thead>
                <tr>
                    <th class="col-klien">Klien</th>
                    <th class="col-nomor">Nomor</th>
                    <th class="col-tgl-tagih">Tgl Tagih</th>
                    <th class="col-jatuh-tempo">Jatuh Tempo</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-bayar">Bayar</th>
                    <th class="col-sisa">Sisa</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $t)
                    @php
                        // Logika PHP Anda tetap sama
                        $status = strtolower($t->status ?? 'draft');
                        $badgeStage = match(true) {
                            in_array($status, ['lunas','paid'])   => 'success',
                            in_array($status, ['sebagian','open']) => 'warning',
                            $status === 'overdue'                  => 'tolak',
                            default                               => 'secondary',
                        };
                        $label = ucfirst($status === 'paid' ? 'lunas' : $status);
                    @endphp
                    <tr>
                        <td class="col-klien">
                            <div class="ellipsis-wrapper fw-semibold">{{ $t->sekolah->nama_sekolah ?? '-' }}</div>
                            <div class="ellipsis-wrapper small-muted">{{ $t->sekolah->alamat ?? '' }}</div>
                        </td>
                        <td class="col-nomor">
                            <a href="{{ route('tagihan.show', $t) }}" class="text-decoration-none ellipsis-wrapper fw-semibold">{{ $t->nomor }}</a>
                            <div class="ellipsis-wrapper small-muted">{{ $t->keterangan ?? '' }}</div>
                        </td>
                        <td class="col-tgl-tagih">
                            <span class="small-muted">{{ optional($t->tanggal_tagihan)->format('d/m/Y') ?? '-' }}</span>
                        </td>
                        <td class="col-jatuh-tempo">
                             @if($t->jatuh_tempo)
                                <span class="badge-stage {{ $t->due_badge ?? 'secondary' }}">
                                    {{ \Carbon\Carbon::parse($t->jatuh_tempo)->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="col-jumlah">{{ rupiah($t->total) }}</td>
                        <td class="col-bayar">{{ rupiah($t->terbayar) }}</td>
                        <td class="col-sisa">{{ rupiah($t->sisa) }}</td>
                        <td class="col-status">
                            <span class="badge-stage {{ $badgeStage }}">{{ $label }}</span>
                        </td>
                        <td class="col-aksi">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary round dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Aksi
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('tagihan.show',$t) }}">Detail</a></li>
                                    <li><a class="dropdown-item" href="{{ route('tagihan.edit',$t) }}">Edit</a></li>
                                    @if($t->wa_url)
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('tagihan.wa', $t->id) }}">Kirim WA</a></li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('tagihan.destroy',$t) }}" method="post" onsubmit="return confirm('Hapus tagihan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">Hapus</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state-cell">
                            <div class="fs-4 mb-2">📑</div>
                            <div class="fw-semibold">Belum ada data</div>
                            <div class="small-muted">Data tagihan akan muncul di sini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($items ?? null,'firstItem'))
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
            Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} data
        </div>
        <div>
            {{ ($items ?? null)?->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
