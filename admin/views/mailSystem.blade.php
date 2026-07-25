@extends('magicAdmin::templateAdmin')

@section('title', 'Mail')

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="mailSystemAdmin"></div>
        @vite('admin/js/mailSystemAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
