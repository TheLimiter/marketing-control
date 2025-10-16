@extends('layouts.app')
@section('content')

@push('styles')
<style>
/* =========================================================
   Static Width Table Layout for Modul
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
.col-kode     { width: 120px; }
.col-nama     { width: 300px; }
.col-kategori { width: 200px; }
.col-versi    { width: 100px; }
.col-harga    { width: 150px; }
.col-status   { width: 120px; }
.col-aksi     { width: 100px; text-align: center; }

/* Utilitas */
.ellipsis-wrapper {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fw-semibold { font-weight: 600; color: var(--text-primary); }

/* Empty state */
.empty-state-cell {
    text-align: center;
    padding: 40px;
}
</style>
@endpush

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="h-page">Modul</div>
            <div class="subtle">Kelola daftar modul yang tersedia</div>
        </div>
        <a href="{{ route('modul.create') }}" class="btn btn-primary round">
            <i class="bi bi-plus-lg me-1"></i> Tambah Modul
        </a>
    </div>

    {{-- Toolbar --}}
    <form method="GET" class="card card-toolbar mb-4">
        <div class="toolbar">
            <div class="field flex-grow-1" style="min-width:320px">
                <label>Cari nama modul</label>
                <input type="text" name="q" class="input-soft" value="{{ request('q') }}" placeholder="Ketik nama modul">
            </div>
            <div class="ms-auto d-flex align-items-end">
                <button class="btn btn-primary round">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
            </div>
        </div>
    </form>

    {{-- Tabel Utama --}}
    <div class="card p-0">
        <div class="table-container">
            <table class="static-table">
                <thead>
                    <tr>
                        <th class="col-kode">Kode</th>
                        <th class="col-nama">Nama</th>
                        <th class="col-kategori">Kategori</th>
                        <th class="col-versi">Versi</th>
                        <th class="col-harga">Harga</th>
                        <th class="col-status">Status</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $m)
                        <tr>
                            <td class="col-kode fw-medium">{{ $m->kode }}</td>
                            <td class="col-nama">
                                <a href="{{ route('modul.show', $m->id) }}" class="text-decoration-none fw-semibold ellipsis-wrapper">
                                    {{ $m->nama }}
                                </a>
                            </td>
                            <td class="col-kategori">
                                <div class="ellipsis-wrapper">{{ $m->kategori }}</div>
                            </td>
                            <td class="col-versi">{{ $m->versi }}</td>
                            <td class="col-harga">
                                @php $h = $m->harga_default ?? null; @endphp
                                {{ is_null($h) ? '—' : 'Rp '.number_format((float)$h, 0, ',', '.') }}
                            </td>
                            <td class="col-status">
                                <span class="badge-stage {{ $m->aktif ? 'success' : 'secondary' }}">
                                    {{ $m->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td class="col-aksi">
                                <div class="dropdown">
                                   <button type="button" class="btn btn-sm btn-outline-secondary round" data-bs-toggle="dropdown" aria-expanded="false">
                                       Aksi <i class="bi bi-chevron-down"></i>
                                   </button>
                                   <ul class="dropdown-menu dropdown-menu-end">
                                       <li>
                                           <a href="{{ route('modul.edit', $m->id) }}" class="dropdown-item">
                                               <i class="bi bi-pencil me-2"></i>Edit
                                           </a>
                                       </li>
                                       <li>
                                           <form action="{{ route('modul.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus modul ini?')">
                                               @csrf
                                               @method('DELETE')
                                               <button type="submit" class="dropdown-item text-danger">
                                                   <i class="bi bi-trash me-2"></i>Hapus
                                               </button>
                                           </form>
                                       </li>
                                   </ul>
                               </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state-cell">
                                Belum ada modul.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($items->hasPages())
        <div class="card-footer">
            {{ $items->links() }}
        </div>
        @endif
    </div>
@endsection

