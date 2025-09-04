@props(['title'=>'','subtitle'=>null])
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0">{{ $title }}</h5>
    @if($subtitle)<div class="text-muted small">{{ $subtitle }}</div>@endif
  </div>
  <div class="d-flex align-items-center gap-2">{{ $slot }}</div>
</div>
