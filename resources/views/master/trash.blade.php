@extends('layouts.app')
@section('content')
<h5 class="mb-3">Sampah Master Sekolah</h5>
<table class="table table-sm">
  <thead><tr><th>Nama</th><th>Dihapus</th><th class="text-end">Aksi</th></tr></thead>
  <tbody>
    @forelse($items as $m)
      <tr>
        <td>{{ $m->nama_sekolah }}</td>
        <td>{{ $m->deleted_at }}</td>
        <td class="text-end">
          <form action="{{ route('master.restore',$m->id) }}" method="post" class="d-inline">@csrf
            <button class="btn btn-success btn-sm">Restore</button>
          </form>
          <form action="{{ route('master.force',$m->id) }}" method="post" class="d-inline"
                onsubmit="return confirm('Hapus permanen?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">Hapus Permanen</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="3" class="text-muted">Kosong</td></tr>
    @endforelse
  </tbody>
</table>
{{ $items->links() }}
@endsection
