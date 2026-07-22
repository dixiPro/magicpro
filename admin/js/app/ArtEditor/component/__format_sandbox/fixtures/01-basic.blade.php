@extends('layouts.app')

@section('title', 'Home page')

@section('content')
<div class="wrap">
@if($user)
<p>Hello {{ $user->name }}</p>
@foreach($items as $item)
<li>{{ $item }}</li>
@endforeach
@else
<p>Guest</p>
@endif
</div>
@endsection
