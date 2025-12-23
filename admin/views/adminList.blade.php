@php($GLOBALS['wide'] = 'middle')

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('admins_title')
@endsection

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="edit_users"></div>
        @vite('admin/js/editUsers.js')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
