@extends('magicAdmin::templateAdmin')

@section('title', 'Редактируем статью')

@section('body')
    <div id="art_editor" class="flex-grow-1"></div>
@endsection

@section('script')


    @vite('admin/js/artEditor.js')


@endsection
