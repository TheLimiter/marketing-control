@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Kelola Pengguna</h4>
  <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Tambah User</a>
</div>

@if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if(session('err')) <div class="alert alert-danger">{{ session('err') }}</div> @endif

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Nama</th><th>Email</th><th>Wajib Ganti?</th><th class="text-end">Aksi</th>
      </tr></thead>
      <tbody>
      @forelse($users as $u)
        <tr>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>{!! $u->must_change_password ? '<span class="badge text-bg-warning">Ya</span>' : '<span class="badge text-bg-success">Tidak</span>' !!}</td>
          <td class="text-end">
            <form action="{{ route('admin.users.reset',$u) }}" method="post" class="d-inline">@csrf
              <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Reset password user ini?')">Reset PW</button>
            </form>
            <a href="{{ route('admin.users.edit',$u) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('admin.users.destroy',$u) }}" method="post" class="d-inline">@csrf @method('delete')
              <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center text-muted">Belum ada user</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
