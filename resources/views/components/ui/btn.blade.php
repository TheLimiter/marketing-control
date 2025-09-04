@props(['variant'=>'primary','size'=>'sm','as'=>'button','href'=>null])
@php $classes="btn btn-$variant btn-$size"; @endphp
@if($as==='a')
  <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
  <button {{ $attributes->class($classes) }} type="submit">{{ $slot }}</button>
@endif
