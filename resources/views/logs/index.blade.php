@extends('layouts.app')
@section('content')
<h5 class="mb-3">Activity Logs</h5>
<table class="table table-sm align-middle">
  <thead>
    <tr>
      <th>#</th><th>Waktu</th><th>User</th><th>Aksi</th>
      <th>Entity</th><th>Before → After</th><th>IP</th>
    </tr>
  </thead>
  <tbody>
    @foreach($logs as $l)
    <tr>
      <td>{{ $l->id }}</td>
      <td>{{ $l->created_at }}</td>
      <td>{{ $l->user->name ?? '-' }}</td>
      <td><span class="badge bg-secondary">{{ $l->action }}</span></td>
      <td>{{ $l->entity_type }}#{{ $l->entity_id }}</td>
      <td style="max-width: 520px;">
        @if($l->before || $l->after)
          <pre class="mb-0 small">{{ json_encode(['before'=>$l->before,'after'=>$l->after], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        @else - @endif
      </td>
      <td>{{ $l->ip }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
{{ $logs->links() }}
@endsection
