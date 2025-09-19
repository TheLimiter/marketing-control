@props(['before'=>[], 'after'=>[]])

@php
  $keys = collect(array_keys(($before ?? []) + ($after ?? [])))->unique();
@endphp

@if($keys->isNotEmpty())
  <div class="mt-2 small">
    @foreach($keys as $k)
      @php $b = $before[$k] ?? null; $a = $after[$k] ?? null; @endphp
      @if($b !== $a)
        <div>
          <code>{{ $k }}</code>:
          <span class="text-danger text-decoration-line-through">{{ is_scalar($b)? $b : json_encode($b, JSON_UNESCAPED_UNICODE) }}</span>
          <span class="mx-1">â†’</span>
          <span class="text-success fw-semibold">{{ is_scalar($a)? $a : json_encode($a, JSON_UNESCAPED_UNICODE) }}</span>
        </div>
      @endif
    @endforeach
  </div>
@endif
