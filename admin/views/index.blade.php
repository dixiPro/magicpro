@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title', 'Админка 1.1.3')

@section('body')
    <h1>Админка</h1>

    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#t1">Старт</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t2">Setup</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t3">Import</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t4">Export</a></li>
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
                <a href="{{ route('magic.cleatCahe') }}">Очистить кеш</a>
            </div>
            {{-- Чистим кеш --}}
            @if (session('clearCacheStatus'))
                <div class="alert alert-info">
                    <p>Результат очистки кеша: 0 - успех</p>
                    <ul>
                        <li>Кеш: {{ session('clearCacheStatus.cache') }}</li>
                        <li>Конфиг: {{ session('clearCacheStatus.config') }}</li>
                        <li>Маршруты: {{ session('clearCacheStatus.route') }}</li>
                        <li>Вьюхи: {{ session('clearCacheStatus.view') }}</li>
                        <li>События: {{ session('clearCacheStatus.event') }}</li>
                    </ul>
                </div>
            @endif

            <div>
                <a href="{{ route('magic.testWrite') }}">Права на запись</a>
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
                    Перегенирация статей
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
                <a href="{{ route('magic.exportArticle', ['id' => 1]) }}">Экспорт таблицы Article</a>
            </div>

            <div>
                <a href="{{ route('magic.downloadDb') }}">Экспорт БД</a>
            </div>

        </div>

        <div class="tab-pane fade" id="t3">
            <div class="my-3">
                <div class="my-2"><strong>Импорт</strong></div>
                <form action="/a_dmin/importArticle" method="POST" enctype="multipart/form-data"
                    class="p-3 border rounded bg-light">
                    @csrf

                    <div class="mb-3">
                        <label for="file" class="form-label fw-bold">Выберите файл JSON</label>
                        <input type="file" name="file" id="file" accept=".json, .xml" class="form-control"
                            required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="writeBase" value="1" id="writeBase">
                        <label class="form-check-label" for="writeBase">
                            Записать изменения в базу данных
                        </label>
                        <div class="form-text">Если не отмечено — будет только проверка, без сохранения.</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="typeFile" checked id="typeFileJson"
                                value="json" required>
                            <label class="form-check-label" for="typeFileJson">
                                из JSON файла
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="typeFile" id="typeFileXml" value="xml">
                            <label class="form-check-label" for="typeFileXml">
                                из XML файла
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Импортировать</button>
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

    <div class="mt-4">Текущий пользователь</div>

    @php
        DumpHelper::dump(Auth::guard('magic')->user());
    @endphp

    {{-- @php phpinfo(); @endphp --}}

@endsection
