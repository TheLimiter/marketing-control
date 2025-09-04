@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('prospek.create') }}" class="btn btn-primary">Tambah Prospek</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>MOU</th>
                    <th>TTD</th>
                    <th>Hasil</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i)
                <tr>
                    <td>{{ $i->calon->nama ?? '-' }}</td>
                    <td>
                        {{-- Menggunakan accessor has_mou --}}
                        @if($i->has_mou)
                            <a href="{{ route('prospek.mou.download', $i->id) }}" target="_blank">Download</a><br>
                            <small>{{ $i->mou_at }}</small>
                        @else
                            <span class="text-danger">Belum</span>
                        @endif
                    </td>
                    <td>
                        {{-- Menggunakan accessor is_ttd --}}
                        @if($i->is_ttd)
                            <span class="text-success">Sudah</span><br>
                            <small>{{ $i->ttd_at }}</small>
                        @else
                            <span class="text-danger">Belum</span>
                        @endif
                    </td>
                    <td>{{ $i->hasil }}</td>
                    <td>
                        {{-- Tombol "Jadikan Klien" hanya aktif jika MOU dan TTD sudah lengkap --}}
                        @if($i->has_mou && $i->is_ttd)
                          <form action="{{ route('prospek.to-klien', $i) }}" method="post" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-primary">Jadikan Klien</button>
                          </form>
                        @else
                          <button class="btn btn-sm btn-secondary" disabled>Jadikan Klien (Lengkapi MOU/TTD)</button>
                        @endif

                        {{-- Tombol untuk MOU --}}
                        <a href="{{ route('prospek.mou.form', $i->id) }}" class="btn btn-sm btn-info">MOU</a>

                        {{-- Tombol untuk TTD (aktif hanya jika belum TTD) --}}
                        <form action="{{ route('prospek.ttd', $i->id) }}" method="post" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success" {{ $i->is_ttd ? 'disabled' : '' }}>TTD</button>
                        </form>

                        {{-- Tombol untuk membatalkan TTD (aktif hanya jika sudah TTD) --}}
                        <form action="{{ route('prospek.ttd.un', $i->id) }}" method="post" class="d-inline" onsubmit="return confirm('Batalkan TTD?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-warning" {{ $i->is_ttd ? '' : 'disabled' }}>Batalkan TTD</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
</div>
@endsection
