@props([
  'modules' => collect(),
  'schools' => collect(),
  'grid'    => [],
])

@once
  @push('styles')
    @vite('resources/css/anima-table.css')
    <style>
      .anima-matrix th,.anima-matrix td{vertical-align:middle}
      .anima-matrix thead th{position:sticky;top:0;z-index:5;background:#fff}
      .anima-matrix .col-name{min-width:260px}
      .anima-matrix .col-progress{width:180px}
      .anima-matrix .mod-title{max-width:92px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;font-size:13px;color:#2a3142}
      .chk{width:18px;height:18px;cursor:pointer}
      .add-btn{width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;line-height:1}
    </style>
  @endpush
@endonce

<div class="table-responsive">
  <table class="table table-sm table-hover table-compact anima-matrix">
    <thead>
      <tr>
        <th class="col-name">Sekolah</th>
        @foreach($modules as $i => $m)
          <th class="text-center" title="{{ $m->nama }}">
            <div>{{ $i+1 }}</div>
            <small class="text-muted mod-title">{{ $m->nama }}</small>
          </th>
        @endforeach
        <th class="col-progress text-center">Progress</th>
      </tr>
    </thead>
    <tbody>
    @foreach($schools as $s)
      @php $sid=$s->id; $doneCount=0; $isAdmin = auth()->user()?->hasRole('admin') ?? false; @endphp
      <tr>
        <td class="col-name">
          <a href="{{ route('progress.show',$sid) }}" class="text-decoration-none">{{ $s->nama_sekolah }}</a>
          @if($isAdmin)
            <form action="{{ route('progress.ensure', $sid) }}" method="post" class="d-inline ms-2">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-primary" title="Lengkapi baris modul 1-9">Lengkapi 1-9</button>
            </form>
          @endif
        </td>

        @foreach($modules as $m)
          @php
            $cell = $grid[$sid][$m->id] ?? ['pm'=>null,'done'=>false];
            if($cell['done']) $doneCount++;
          @endphp
          <td class="text-center">
            @if($cell['pm'])
              <form action="{{ route('progress.toggle', [$sid, $cell['pm']->id]) }}" method="post" class="d-inline">
                @csrf
                @php $disabled = $isAdmin ? '' : 'disabled'; @endphp
                <input type="checkbox" class="form-check-input chk" {{ $cell['done'] ? 'checked' : '' }} {{ $disabled }}
                       @if($isAdmin) onchange="this.form.submit()" @endif>
              </form>
            @else
              @if($isAdmin)
              <form action="{{ route('progress.attach', [$sid, $m->id]) }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm add-btn" title="Tambah modul ini">+</button>
              </form>
              @endif
            @endif
          </td>
        @endforeach

        @php $total = max(1, $modules->count()); $percent = (int) floor(($doneCount / $total) * 100); @endphp
        <td class="col-progress">
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted">{{ $percent }}%</small>
            <div class="progress flex-grow-1" style="height:8px">
              <div class="progress-bar bg-info" style="width: {{ $percent }}%"></div>
            </div>
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

@if(method_exists($schools,'links'))
  <div class="mt-2">{{ $schools->links() }}</div>
@endif
