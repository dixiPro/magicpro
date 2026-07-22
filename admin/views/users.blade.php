@extends('magicAdmin::templateAdmin')

@section('title', 'Users')

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="edit_users"></div>
        @vite('admin/js/editLaravelUsers.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
