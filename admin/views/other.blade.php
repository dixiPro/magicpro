@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('other')
@endsection

@section('body')
    <h2>@magic_msg('other')</h2>

    @include('magicAdmin::iconsList')
@endsection
