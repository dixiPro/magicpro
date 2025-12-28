@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('file_manager')
@endsection

@section('body')
    <div id="file_manager" class="flex-grow-1"></div>
@endsection

@section('script')
    @vite('admin/js/fileManager.js')
@endsection
