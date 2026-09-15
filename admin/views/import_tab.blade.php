@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('import_tab')
@endsection

@section('body')
    <h1>@magic_msg('import_tab')</h1>

    {{-- API импорта отвечает только администратору: страница, открытая всем
         вошедшим, показывала бы форму, которая не работает --}}
    @if (Auth::guard('magic')->user()?->role === 'admin')
        <div id="importAdmin"></div>
        @vite('admin/js/importAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
