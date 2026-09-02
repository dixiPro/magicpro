@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('import_tab')
@endsection

@section('body')
    <h1>@magic_msg('import_tab')</h1>

    <div id="importAdmin"></div>
    @vite('admin/js/importAdmin.js', 'vendor/dixipro/magicpro')
@endsection
