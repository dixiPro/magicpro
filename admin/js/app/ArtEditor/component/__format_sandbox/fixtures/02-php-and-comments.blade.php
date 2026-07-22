{{-- top comment --}}
@php
$a = 1;
$b=[1,2,3];
foreach($b as $x){ $a += $x; }
@endphp

<div>
@php $inline = true; @endphp
<span>{{ $a }}</span>
</div>

@if($cond)
@php
    $nested = 'deep';
@endphp
<p>{{ $nested }}</p>
@endif
