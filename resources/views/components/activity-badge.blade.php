@props(['action'])
@php
  $map = [
    'stage.change'   => 'warning',
    'mou.upload'     => 'info',
    'module.attach'  => 'primary',
    'module.use'     => 'success',
    'module.status'  => 'secondary',
    'invoice.create' => 'primary',
    'invoice.paid'   => 'success',
  ];
  $color = $map[$action] ?? 'secondary';
@endphp
<span class="badge text-bg-{{ $color }}">{{ $action }}</span>
