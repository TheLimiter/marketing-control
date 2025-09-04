@extends('layouts.app')
@section('content')
<h4 class="mb-3">Edit User</h4>
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="post" action="{{ route('admin.users.update',$user) }}" class="card p-3">
  @csrf @method('put')
  <div class="mb-3">
    <label class="form-label">Nama</label>
    <input type="text" name="name" class="form-control" required value="{{ old('name',$user->name) }}">
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required value="{{ old('email',$user->email) }}">
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Kembali</a>
  </div>
</form>
@endsection
