@php($GLOBALS['wide'] = 'middle')

@extends('magicAdmin::templateAdmin')

@section('title', 'Список статей')

@section('body')

    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="edit_users"></div>
        @vite('admin/js/editUsers.js')
    @else
        <div>Недостаточно прав</div>
    @endif

@endsection
