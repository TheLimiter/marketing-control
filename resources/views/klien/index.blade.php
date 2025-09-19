@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Daftar Klien</h4>
    <a href="{{ route('klien.create') }}" class="btn btn-primary">Tambah Klien</a>
</div>

{{-- Menampilkan pesan sukses dari konversi dan penambahan klien --}}
@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info">{{ session('info') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th style="width:72px;">No</th>
                    <th>Nama</th>
                    <th>Tgl MOU</th>
                    <th>Status TTD</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $k)
                <tr>
                    <td>{{ $items->firstItem() + $loop->index }}</td>
                    <td>{{ $k->nama }}</td>
                    <td>{{ $k->tanggal_mou ?? 'â€”' }}</td>
                    <td>{{ ($k->status_ttd === 'sudah' || $k->status_ttd == 1) ? 'Sudah' : 'Belum' }}</td>
                    <td class="text-end">
                        <a href="{{ route('klien.edit', $k) }}" class="btn btn-sm btn-warning">Edit</a>
                        {{-- tambahkan tombol aksi lain yang kamu perlukan di sini --}}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada klien</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $items->links() }}</div>
@endsection
