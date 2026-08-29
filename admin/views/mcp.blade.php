@extends('magicAdmin::templateAdmin')

@section('title', 'MCP')

@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="mcpAdmin"></div>
        @vite('admin/js/mcpAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
