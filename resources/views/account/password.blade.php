@extends('layouts.app')
@section('content')
<h4 class="mb-3">Ganti Password</h4>
@if(session('warn')) <div class="alert alert-warning">{{ session('warn') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif
<form method="post" action="{{ route('account.password.update') }}" class="card p-3">
  @csrf
  <div class="mb-3">
    <label class="form-label">Password Saat Ini</label>
    <input type="password" name="current_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password Baru</label>
    <input type="password" name="password" class="form-control" required>
    <div class="form-text">Min 8 karakter, kombinasi huruf besar/kecil & angka.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Konfirmasi Password Baru</label>
    <input type="password" name="password_confirmation" class="form-control" required>
  </div>
  <button class="btn btn-primary">Simpan</button>
</form>
@endsection
