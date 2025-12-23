@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('atricle_edit')
@endsection

@section('body')
    <div id="art_editor" class="flex-grow-1"></div>
@endsection

@section('script')
    @vite('admin/js/artEditor.js')
@endsection
