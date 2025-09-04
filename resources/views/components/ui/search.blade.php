@props(['name'=>'q','value'=>'','placeholder'=>'Cari...'])
<form method="get" class="input-group input-group-sm" style="max-width:280px">
  <input type="text" name="{{ $name }}" value="{{ $value }}" class="form-control" placeholder="{{ $placeholder }}">
  <x-ui.btn> Cari </x-ui.btn>
</form>
