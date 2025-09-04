@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0">Tambah Sekolah</h5>
    <div class="text-muted small">Isi data dasar sekolah</div>
  </div>
  <div class="page-toolbar">
    <a href="{{ route('master.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
  </div>
</div>

<form action="{{ route('master.store') }}" method="post" class="mb-4">
  @csrf
  @include('master.form', ['row' => null])
</form>
@endsection
