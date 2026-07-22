@switch($type)
@case(1)
<p>one</p>
@break
@case(2)
<p>two</p>
@break
@default
<p>other</p>
@endswitch

@forelse($users as $u)
<li>{{ $u }}</li>
@empty
<li>none</li>
@endforelse
