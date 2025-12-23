@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('vesrsion')
@endsection

@section('body')
    <h1>@magic_msg('title') @magic_msg('vesrsion')</h1>

    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#t1">@magic_msg('start')</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t2">@magic_msg('setup')</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t3">@magic_msg('import_tab')</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t4">@magic_msg('export_tab')</a></li>
    </ul>
    <div class="tab-content mt-3">


        <div class="tab-pane fade" id="t2">
            <div id="setup"></div>
            @vite('admin/js/setup.js')
        </div>

        <div class="tab-pane fade  show active" id="t1">
            <div class="m2">
                {{ now() }} {{ now()->timezoneName }}
            </div>
            <div>
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

            <div>
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

            <div>
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

            <div><a href="/a_dmin/phpinfo">phpinfo</a></div>
        </div>
        <div class="tab-pane fade" id="t4">
            <div>
                <a href="{{ route('magic.exportArticle', ['id' => 1]) }}">@magic_msg('export_article_table')</a>
            </div>

            <div>
                <a href="{{ route('magic.downloadDb') }}">@magic_msg('export_db')</a>
            </div>

        </div>

        <div class="tab-pane fade" id="t3">
            <div class="my-3">
                <div class="my-2"><strong>@magic_msg('import')</strong></div>
                <form action="/a_dmin/importArticle" method="POST" enctype="multipart/form-data"
                    class="p-3 border rounded bg-light">
                    @csrf

                    <div class="mb-3">
                        <label for="file" class="form-label fw-bold">@magic_msg('select_json_file')</label>
                        <input type="file" name="file" id="file" accept=".json, .xml" class="form-control"
                            required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="writeBase" value="1" id="writeBase">
                        <label class="form-check-label" for="writeBase">
                            @magic_msg('save_changes_to_db')
                        </label>
                        <div class="form-text">@magic_msg('import_check_only_hint')</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="typeFile" checked id="typeFileJson"
                                value="json" required>
                            <label class="form-check-label" for="typeFileJson">
                                @magic_msg('from_json')
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="typeFile" id="typeFileXml" value="xml">
                            <label class="form-check-label" for="typeFileXml">
                                @magic_msg('from_xml')
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">@magic_msg('import')</button>
                </form>

            </div>

            {{-- Вывод результата импорта --}}
            @if (session('importResult'))
                <div class="alert alert-info mt-3">
                    @foreach (session('importResult', []) as $item)
                        <div>
                            <b>{{ $item['name'] }}</b>: {{ $item['msg'] }}
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    <div class="mt-4">@magic_msg('current_user')</div>

    @php
        DumpHelper::dump(Auth::guard('magic')->user());
    @endphp

    {{-- @php phpinfo(); @endphp --}}

@endsection
