@extends('magicAdmin::templateAdmin')

@section('title', 'Cron')

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="cronAdmin"></div>
        @vite('admin/js/cronAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
