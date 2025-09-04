@props(['type'=>'info','dismiss'=>true])
@php $class = [
 'success'=>'alert-success','danger'=>'alert-danger','warning'=>'alert-warning','info'=>'alert-primary'
][$type] ?? 'alert-primary'; @endphp

<div {{ $attributes->class("alert $class d-flex align-items-start gap-2") }} role="alert">
  <div>{{ $slot }}</div>
  @if($dismiss)<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>@endif
</div>
