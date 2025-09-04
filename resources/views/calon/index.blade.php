@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Calon Klien</h4>
  <a href="{{ route('calon.create') }}" class="btn btn-primary">Tambah</a>
</div>

{{-- Menampilkan pesan sukses/info dari controller --}}
@if(session('ok'))      <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div> @endif
@if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif


<div class="card">
  <div class="table-responsive">
    <table class="table table-hover m-0">
      <thead>
        <tr>
          <th style="width:72px;">No</th>
          <th>Nama</th>
          <th>Narahubung</th>
          <th>Jenjang</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i)
        <tr>
          <td>{{ $items->firstItem() + $loop->index }}</td>
          <td><a href="{{ route('calon.show', $i) }}">{{ $i->nama }}</a></td>
          <td>{{ $i->narahubung }}</td>
          <td>{{ $i->jenjang }}</td>
          <td class="text-end">
            {{-- Tombol Edit --}}
            <a href="{{ route('calon.edit', $i) }}" class="btn btn-sm btn-warning">Edit</a>

            {{-- Tombol Hapus --}}
            <form action="{{ route('calon.destroy', $i) }}" method="post" class="d-inline" onsubmit="return confirm('Hapus data ini?')">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-danger">Hapus</button>
            </form>

            {{-- Tombol Jadikan Prospek --}}
            {{-- Ini adalah alur yang benar, mengubah calon klien menjadi prospek terlebih dahulu --}}
            <form action="{{ route('calon.jadikan-prospek', $i) }}" method="post" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-primary">Jadikan Prospek</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted">Belum ada data</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>

@endsection
