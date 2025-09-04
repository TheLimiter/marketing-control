@props(['label'=>'','value'=>'0','icon'=>null,'tone'=>'primary'])
<div {{ $attributes->class('card card-stat') }}>
  <div class="card-body d-flex align-items-center gap-3">
    @if($icon)
      <div class="rounded-circle p-3 text-bg-{{ $tone }}">
        <i class="bi {{ $icon }}"></i>
      </div>
    @endif
    <div>
      <div class="label">{{ $label }}</div>
      <div class="value">{{ $value }}</div>
    </div>
  </div>
</div>
