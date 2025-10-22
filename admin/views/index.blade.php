@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title', 'Админка')

@section('body')
    <h1>Админка</h1>
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
        <a href="{{ route('magic.exportArticle') }}">Экспорт табицы Article</a>
    </div>

    <div class="my-3">
        <div class="my-2"><strong>Импорт</strong></div>
        <form action="/a_dmin/importArticle" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-2">
                <label for="file">Выберите файл JSON:</label>
                <input type="file" name="file" id="file" accept=".json" required>
            </div>
            <div class="mb-2">
                <label>
                    <input type="checkbox" name="writeBase" value="1">
                    Записать изменения в базу данных
                </label>
                <small style="display:block; color:gray;">Если не отмечено — будет только проверка, без сохранения.</small>
            </div>
            <button type="submit">Импортировать</button>
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

    <div><a href="/a_dmin/phpinfo">phpinfo</a></div>


    <div class="mt-4">Текущий пользователь</div>

    @php
        DumpHelper::dump(Auth::guard('magic')->user());
    @endphp


    {{-- @php phpinfo(); @endphp --}}

@endsection
