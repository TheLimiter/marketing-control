@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="text-muted small">Aktivitas Sistem</div>
    <h5 class="mb-0">Log Aktivitas</h5>
  </div>
  <div><a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">← Kembali</a></div>
</div>

<form method="get" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label mb-1">Action</label>
      <input type="text" name="action" class="form-control form-control-sm" value="{{ request('action') }}" placeholder="mis. tagihan.create">
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Entity Type</label>
      <input type="text" name="entity_type" class="form-control form-control-sm" value="{{ request('entity_type') }}" placeholder="App\\Models\\TagihanKlien">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-1">Sekolah (ID)</label>
      <input type="number" name="school" class="form-control form-control-sm" value="{{ request('school') }}">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-1">Dari</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-1">Sampai</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
  </div>
  <div class="mt-2">
    <button class="btn btn-sm btn-primary">Filter</button>
  </div>
</form>

<div class="row g-3">
  <div class="col-lg-8">
    @include('components.activity-feed', ['logs' => $logs])
    <div class="mt-3">{{ $logs->links() }}</div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header py-2"><strong>Top Actions</strong></div>
      <ul class="list-group list-group-flush">
        @foreach($topActions as $t)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $t->action }}</span>
            <span class="badge bg-secondary">{{ $t->total }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
