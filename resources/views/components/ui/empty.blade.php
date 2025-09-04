@props(['icon'=>'inbox','text'=>'Belum ada data.'])
<div class="text-center text-muted py-5">
  <i class="bi bi-{{ $icon }} display-6 d-block mb-2"></i>
  <div>{{ $text }}</div>
  {{ $slot }}
</div>
