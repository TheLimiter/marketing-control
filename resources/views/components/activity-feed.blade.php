<div class="list-group">
  @forelse($logs as $log)
    <div class="list-group-item">
      <div class="d-flex w-100 justify-content-between">
        <h6 class="mb-1">{{ $log->title ?? ($log->action . ' • ' . class_basename($log->entity_type)) }}</h6>
        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
      </div>
      <div class="small text-muted">
        {{ $log->action }}
        @if($log->sekolah) • {{ $log->sekolah->nama_sekolah ?? 'Sekolah #' . $log->master_sekolah_id }} @endif
        @if($log->user) • oleh {{ $log->user->name }} @endif
      </div>
      @if(!empty($log->after))
        <pre class="small mt-2 mb-0">{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
      @endif
    </div>
  @empty
    <div class="text-muted small">Belum ada aktivitas.</div>
  @endforelse
</div>
