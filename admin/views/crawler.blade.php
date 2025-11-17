@php($GLOBALS['wide'] = 'middle')

@extends('magicAdmin::templateAdmin')

@section('title', 'Crawler')

@section('body')

    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="crawler"></div>
        @vite('admin/js/crawler.js')
    @else
        <div>Недостаточно прав</div>
    @endif

@endsection
