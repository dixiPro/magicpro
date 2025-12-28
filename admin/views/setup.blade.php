@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('setup')
@endsection

@section('body')
    <h1>@magic_msg('setup')</h1>

    <div id="setup"></div>
    @vite('admin/js/setup.js')
@endsection
