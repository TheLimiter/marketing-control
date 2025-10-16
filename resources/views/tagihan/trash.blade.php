@extends('layouts.app')

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

/* Penentuan Lebar Kolom */
.col-klien { width: 30%; }
.col-nomor { width: 20%; }
.col-total { width: 15%; text-align: right; }
.col-dihapus { width: 15%; }
.col-aksi { width: 20%; text-align: center; }


/* Utilitas untuk Ellipsis (...) */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }
.small-muted { font-size: 0.9em; color: var(--text-secondary); }

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
        <div class="eyebrow">Arsip</div>
        <h1 class="h-page mb-0">Tagihan Terhapus</h1>
        <div class="subtle">Data tagihan yang telah dihapus sementara.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('tagihan.index') }}" class="btn btn-ghost round">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Tagihan
        </a>
    </div>
</div>

<div class="card p-0">
    <div class="table-container">
        <table class="static-table">
            <thead>
                <tr>
                    <th class="col-klien">Klien</th>
                    <th class="col-nomor">Nomor</th>
                    <th class="col-total">Total</th>
                    <th class="col-dihapus">Dihapus Pada</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $t)
                    <tr>
                        <td class="col-klien">
                            <div class="ellipsis-wrapper fw-semibold">{{ $t->sekolah->nama_sekolah ?? '-' }}</div>
                        </td>
                        <td class="col-nomor">
                            <div class="ellipsis-wrapper fw-semibold">{{ $t->nomor }}</div>
                        </td>
                        <td class="col-total">{{ rupiah($t->total) }}</td>
                        <td class="col-dihapus">
                            <span class="small-muted" title="{{ $t->deleted_at->format('d M Y, H:i') }}">
                                {{ $t->deleted_at->diffForHumans() }}
                            </span>
                        </td>
                        <td class="col-aksi">
                            <div class="d-flex gap-2 justify-content-center">
                                <form action="{{ route('tagihan.restore', $t->id) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success round">Pulihkan</button>
                                </form>
                                <form action="{{ route('tagihan.forceDelete', $t->id) }}" method="post" class="d-inline" onsubmit="return confirm('Hapus permanen tagihan ini? Tindakan ini TIDAK BISA dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger round" type="submit">Hapus Permanen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state-cell">
                            <div class="fs-4 mb-2">🗑️</div>
                            <div class="fw-semibold">Tidak ada data di sampah</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
    <div class="card-footer d-flex justify-content-end">
        {{ $items->links() }}
    </div>
    @endif
</div>
@endsection
