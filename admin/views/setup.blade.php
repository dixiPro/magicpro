@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('setup')
@endsection

@section('body')
    <h1>@magic_msg('setup')</h1>

    <div class="my-2">
        {{ now() }} {{ now()->timezoneName }}
    </div>

    <div class="my-2">
        <a href="{{ route('magic.cleatCahe') }}">@magic_msg('cache_clear')</a>
    </div>
    {{-- Чистим кеш --}}
    @if (session('clearCacheStatus'))
        <div class="alert alert-info">
            <p>@magic_msg('cahceDelteResult')</p>
            <ul>
                <li>@magic_msg('cache'): {{ session('clearCacheStatus.cache') }}</li>
                <li>@magic_msg('config'): {{ session('clearCacheStatus.config') }}</li>
                <li>@magic_msg('routes'): {{ session('clearCacheStatus.route') }}</li>
                <li>@magic_msg('views'): {{ session('clearCacheStatus.view') }}</li>
                <li>@magic_msg('events'): {{ session('clearCacheStatus.event') }}</li>
            </ul>
        </div>
    @endif

    <div class="my-2">
        <a href="{{ route('magic.testWrite') }}">@magic_msg('write_permissions')</a>
    </div>

    @if (session('testWriteStatus'))
        <div class="alert alert-info">
            @foreach (session('testWriteStatus', []) as $item)
                <div>
                    <b>{{ $item['desc'] }}</b>: {{ $item['value'] }} — {{ $item['result'] }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-2">
        <a href="#"
            onclick="fetch('/a_dmin/api/articles', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({command: 'regenerateAll'})
                })
                .then(r => r.json())
                .then(data => document.getElementById('result').textContent = JSON.stringify(data, null, 2))">
            @magic_msg('regenerate_articles')
        </a>
        <pre id="result"></pre>
    </div>

    @if (session('regenerateArticles'))
        <div class="alert alert-info">
            @foreach (session('regenerateArticles', []) as $item)
                <div>

                </div>
            @endforeach
        </div>
    @endif

    <div class=""><a href="/a_dmin/phpinfo">phpinfo</a></div>

    <div id="setup" class="my-3"></div>
    @vite('admin/js/setup.js')
@endsection
