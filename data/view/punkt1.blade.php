@extends('magic::testTemplate')
@section('body')
==
<x-magic::topmenu1></x-magic::topmenu1>
==

<ul>
@foreach( TreeHelper::getChildrenByName('topMenu') as $child) 
    <li class="nav-item"> <a class="nav-link active" href="/{{$child['name'] }}">{{$child['title'] }}</a> 
    </li> 
@endforeach
<li>123</li>
</ul>
@endsection