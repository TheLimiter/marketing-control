@props(['title'=>null,'actions'=>null])
<div {{ $attributes->merge(['class'=>'card p-3']) }}>
  @if($title || $actions)
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-semibold">{{ $title }}</div>
      <div>{{ $actions }}</div>
    </div>
  @endif
  {{ $slot }}
</div>
