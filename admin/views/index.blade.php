@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('vesrsion')
@endsection

@section('body')
    <h1>@magic_msg('title') @magic_msg('vesrsion')</h1>

    <div class="mt-4">@magic_msg('current_user')</div>

    @php
        DumpHelper::dump(Auth::guard('magic')->user());
    @endphp
@endsection
