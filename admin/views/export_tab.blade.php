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

    @php
        use Illuminate\Support\Facades\File;
        use Illuminate\Support\Facades\Schema;

        $models = collect(File::files(app_path('Models')))
            ->map(fn($f) => 'App\\Models\\' . $f->getFilenameWithoutExtension())
            ->filter(fn($c) => is_subclass_of($c, \Illuminate\Database\Eloquent\Model::class));
    @endphp

    <ul>
        @foreach ($models as $model)
            @php
                $instance = new $model();
                $table = $instance->getTable();
                $columns = Schema::getColumnListing($table);
            @endphp

            <li>
                <strong>{{ class_basename($model) }}</strong>
                <div>table: {{ $table }}</div>
                <ul>
                    @foreach ($columns as $col)
                        <li>{{ $col }}</li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>


    ============

    @php
        use Illuminate\Support\Facades\DB;

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $tables = collect(DB::select('show tables'))->map(fn($r) => array_values((array) $r)[0]);

            $getColumns = fn($table) => collect(DB::select("show columns from `$table`"))->pluck('Field');
        } elseif ($driver === 'pgsql') {
            $tables = collect(
                DB::select("
            select tablename
            from pg_tables
            where schemaname = 'public'
        "),
            )->pluck('tablename');

            $getColumns = fn($table) => collect(
                DB::select(
                    "
                select column_name
                from information_schema.columns
                where table_schema = 'public'
                  and table_name = ?
            ",
                    [$table],
                ),
            )->pluck('column_name');
        } elseif ($driver === 'sqlite') {
            $tables = collect(
                DB::select("
            select name
            from sqlite_master
            where type = 'table'
        "),
            )->pluck('name');

            $getColumns = fn($table) => collect(DB::select("pragma table_info('$table')"))->pluck('name');
        }
    @endphp

    <ul>
        @foreach ($tables as $table)
            <li>
                <strong>{{ $table }}</strong>
                <ul>
                    @foreach ($getColumns($table) as $col)
                        <li>{{ $col }}</li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
@endsection
