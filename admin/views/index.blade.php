@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title', 'Админка')

@section('body')
    <h1>Админка1</h1>
    <pre>
    @php
        DumpHelper::dump($MAGIC_FILE_ROLES);
        print_r(json_encode($MAGIC_FILE_ROLES));
    @endphp
</pre>

    @php
        print_r($user);

    @endphp


    <table class="table table-bordered table-sm table-striped">
        <thead>
            <tr>
                <th>Путь</th>
                <th>Описание</th>
                <th>Существует</th>
                <th>Чтение</th>
                <th>Запись</th>
                <th>Выполнение</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($MAGIC_FILE_ROLES as $item)
                <tr>
                    <td nowrap>{{ $item['value'] }}</td>
                    <td>{{ $item['desc'] ?? '' }}</td>
                    <td>{{ print_r($item['stat'], true) }}</td>

                    {{-- <td>{{ $item['exists'] ? '✅' : '❌' }}</td>
                    <td>{{ $item['readable'] ? '✅' : '❌' }}</td>
                    <td>{{ $item['writable'] ? '✅' : '❌' }}</td>
                    <td>{{ $item['executable'] ? '✅' : '❌' }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>

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
        <a href="{{ route('magic.cleatCahe') }}">Очистить кеш</a>
    </div>


    <h3>Текущий пользователь</h3>
    <pre>{{ json_encode(Auth::guard('magic')->user(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

    {{-- @php phpinfo(); @endphp --}}

@endsection
