@extends('layouts.app')

@section('content')
<h4 class="mb-3">Manajemen Pengguna</h4>

@if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

<div class="card p-3">
  {{-- Form untuk menambah user baru --}}
  <div class="mb-4">
      <h5>Tambah Pengguna Baru</h5>
      <form action="{{ route('users.store') }}" method="post" class="d-flex gap-2">
          @csrf
          <input type="text" name="name" class="form-control" placeholder="Nama" required>
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <button class="btn btn-primary">Tambah</button>
      </form>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th style="width:72px;">No</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Role Aktif</th>
          <th style="width:280px;">Ganti Role</th>
          <th>Status</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $u)
        <tr>
          <td>{{ ($users->firstItem() ?? 0) + $loop->index }}</td>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
          <td>
            <form action="{{ route('users.update', $u) }}" method="post" class="d-flex gap-2">
              @csrf @method('PUT')
              <select name="role" class="form-select">
                @foreach($roles as $role)
                  <option value="{{ $role }}" {{ $u->roles->pluck('name')->contains($role) ? 'selected' : '' }}>
                    {{ ucfirst($role) }}
                  </option>
                @endforeach
              </select>
              <button class="btn btn-primary">Simpan</button>
            </form>
          </td>
          <td>
              @if($u->active)
                  <span class="badge bg-success">Aktif</span>
              @else
                  <span class="badge bg-danger">Nonaktif</span>
              @endif
          </td>
          <td class="text-end">
              @if(auth()->user()->hasRole('Admin'))
                  <form action="{{ route('users.toggle-status', $u) }}" method="post" class="d-inline">
                      @csrf
                      <button class="btn btn-sm btn-{{ $u->active ? 'danger' : 'success' }}">
                          {{ $u->active ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>
                  </form>
              @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">Belum ada pengguna</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-2">{{ $users->links() }}</div>
</div>
@endsection
