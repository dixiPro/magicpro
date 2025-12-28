@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('export_tab')
@endsection

@section('body')
    <h1>@magic_msg('export_tab')</h1>

    <div>
        <a href="{{ route('magic.exportArticle', ['id' => 1]) }}">@magic_msg('export_article_table')</a>
    </div>
    <div>
        <a href="{{ route('magic.downloadDb') }}">@magic_msg('export_db')</a>
    </div>
@endsection
