{{-- opened by window.open from the editor and the left menu: a window of its own --}}
@php($GLOBALS['bare'] = true)

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('file_manager')
@endsection

@section('body')
    <div id="file_manager"></div>
@endsection

@section('script')
    @vite('admin/js/fileManager.js', 'vendor/dixipro/magicpro')
@endsection
