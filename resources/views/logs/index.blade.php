@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    // helper kecil buat link "buka" sesuai entity
    function __log_target_url($l) {
        try {
            if ($l->entity_type === \App\Models\MasterSekolah::class && $l->entity_id) {
                return route('master.edit', $l->entity_id);
            }
            if ($l->entity_type === \App\Models\Mou::class && $l->master_sekolah_id) {
                return route('mou.form', $l->master_sekolah_id);
            }
            if ($l->entity_type === \App\Models\PenggunaanModul::class && $l->master_sekolah_id) {
                return route('penggunaan-modul.index', ['school'=>$l->master_sekolah_id]);
            }
            if ($l->entity_type === \App\Models\TagihanKlien::class && $l->entity_id) {
                // arahkan ke form bayar / detail pdf sesuai yang tersedia
                return route('tagihan.bayar', $l->entity_id);
            }
        } catch (\Throwable $e) {}
        return null;
    }

    // pewarnaan badge based on action (opsional, bisa kamu kembangkan)
    function __action_badge_class($action) {
        return match(true) {
            str_starts_with($action, 'stage.')     => 'bg-dark',
            str_starts_with($action, 'mou.')       => 'bg-primary',
            str_starts_with($action, 'modul.')     => 'bg-info',
            str_starts_with($action, 'tagihan.')   => 'bg-warning text-dark',
            default                                => 'bg-secondary',
        };
    }
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="text-muted small">Audit</div>
    <h5 class="mb-0">Activity Logs</h5>
  </div>
</div>

{{-- Filter --}}
<form method="get" class="card card-toolbar mb-3">
  <div class="toolbar">
    <div class="field" style="min-width:200px">
      <label>Action</label>
      <input type="text" name="action" value="{{ request('action') }}" class="input-soft" placeholder="mis. stage.change">
    </div>
    <div class="field" style="min-width:240px">
      <label>Entity Type</label>
      <input type="text" name="entity_type" value="{{ request('entity_type') }}" class="input-soft" placeholder="App\\Models\\MasterSekolah">
    </div>
    <div class="field" style="min-width:140px">
      <label>Sekolah (ID)</label>
      <input type="number" name="school" value="{{ request('school') }}" class="input-soft">
    </div>
    <div class="field" style="min-width:140px">
      <label>Dari</label>
      <input type="date" name="from" value="{{ request('from') }}" class="input-soft">
    </div>
    <div class="field" style="min-width:140px">
      <label>Sampai</label>
      <input type="date" name="to" value="{{ request('to') }}" class="input-soft">
    </div>
    <div class="ms-auto d-flex align-items-end gap-2">
      <button class="btn btn-primary round">Terapkan</button>
      @if(request()->anyFilled(['action','entity_type','school','from','to']))
        <a href="{{ route('logs.index') }}" class="btn btn-ghost round">Reset</a>
      @endif
    </div>
  </div>
</form>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table table-modern table-sm align-middle mb-0">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th style="min-width:140px">Waktu</th>
          <th>Title / Action</th>
          <th>Sekolah</th>
          <th>Entity</th>
          <th style="min-width:340px">Perubahan (before - after)</th>
          <th style="min-width:100px">User</th>
          <th style="min-width:80px">IP</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $l)
          @php
            $go = __log_target_url($l);
            $badgeClass = __action_badge_class($l->action ?? '');
            $sch = $l->sekolah?->nama_sekolah;
          @endphp
          <tr>
            <td class="text-muted">{{ $l->id }}</td>
            <td>
              <div class="fw-medium">{{ optional($l->created_at)->format('d M Y H:i') }}</div>
              <div class="small text-muted">{{ optional($l->created_at)->diffForHumans() }}</div>
            </td>
            <td>
              <div class="fw-medium">
                {{ $l->title ?? '-' }}
                @if($go)
                  <a class="small ms-2" href="{{ $go }}">buka</a>
                @endif
              </div>
              <span class="badge {{ $badgeClass }}">{{ $l->action }}</span>
            </td>
            <td>
              @if($l->master_sekolah_id)
                <a href="{{ route('master.aktivitas.index', $l->master_sekolah_id) }}" class="text-decoration-none">
                  {{ $sch ? Str::limit($sch, 38) : ('#'.$l->master_sekolah_id) }}
                </a>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td class="small">
              {{ class_basename($l->entity_type) ?: '-' }}@if($l->entity_id)#{{ $l->entity_id }}@endif
            </td>
            <td>
              @if($l->before || $l->after)
                <details>
                  <summary class="small text-muted" style="cursor:pointer">lihat diff</summary>
                  <pre class="mb-0 small border rounded p-2 bg-body-tertiary">{{ json_encode(['before'=>$l->before,'after'=>$l->after], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td class="small">{{ $l->user->name ?? '-' }} </td>
            <td class="small text-muted">{{ $l->ip ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-muted py-4">Belum ada log.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="p-3">
    {{ $logs->links() }}
  </div>
</div>
@endsection
