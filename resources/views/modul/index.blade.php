@extends('layouts.app')
@section('content')
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
                <input type="text" name="q" class="input-soft" value="{{ request('q') }}" placeholder="Ketik nama modul…">
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
        <div class="table-responsive">
            <table class="table table-modern table-compact table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:10%;">Kode</th>
                        <th style="width:30%;">Nama</th>
                        <th style="width:20%;">Kategori</th>
                        <th style="width:10%;">Versi</th>
                        <th style="width:10%;">Status</th>
                        <th class="text-end" style="width:20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $m)
                        <tr>
                            <td>{{ $m->kode }}</td>
                            <td>{{ $m->nama }}</td>
                            <td>{{ $m->kategori }}</td>
                            <td>{{ $m->versi }}</td>
                            <td>
                                <span class="badge {{ $m->aktif ? 'badge-stage klien' : 'badge-stage secondary' }}">
                                    {{ $m->aktif ? 'Aktif' : 'Tidak' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('modul.edit',$m->id) }}" class="btn btn-sm btn-outline-primary round">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <form action="{{ route('modul.destroy',$m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus modul ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger round">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada modul.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-3 p-3 text-center">
        {{ $items->links() }}
    </div>
@endsection
