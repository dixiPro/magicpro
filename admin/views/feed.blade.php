@extends('magicAdmin::templateAdmin')

@section('title', 'Ленты')

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="feedAdmin"></div>
        @vite('admin/js/feedAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
