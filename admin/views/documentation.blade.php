@php
    $GLOBALS['wide'] = 'middle';

    // язык страницы — язык админки: отдельного переключателя нет, чтобы дока и
    // подписи вокруг неё не расходились
    $lang = (string) (\MagicProSrc\Config\MagicGlobals::$INI['LANGUAGE'] ?? 'ru');

    $helpers = \MagicProSrc\Docs\PhpDoc::methods(MproHelper::class, $lang);
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('documentation')
@endsection

@section('body')
    <h1>@magic_msg('documentation')</h1>

    <h2 id="mproHelpers">mproHelpers</h2>

    {{-- список сверху: с него видно весь набор, не листая --}}
    <p class="small">
        @foreach ($helpers as $method)
            <a href="#{{ $method['name'] }}" class="me-2">{{ $method['name'] }}</a>
        @endforeach
    </p>

    @foreach ($helpers as $method)
        <div class="mt-4">
            <h3 id="{{ $method['name'] }}" class="h6 mb-1">
                <code>MproHelper::{{ $method['signature'] }}</code>
            </h3>

            @if ($method['doc'])
                <div class="small">{!! $method['doc'] !!}</div>
            @else
                <div class="small text-danger">@magic_msg('documentation_none')</div>
            @endif
        </div>
    @endforeach
@endsection
