@php($GLOBALS['wide'] = 'middle')

@extends('magicAdmin::templateAdmin')

@section('title', 'Список статей')

@section('body')
    <h2>F!</h2>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($articles->isEmpty())
        <p>Записей нет.</p>
    @else
        <table class="table table-striped  table-sm">
            <thead>
                <tr>
                    <th>id</th>
                    <th>parentId</th>
                    <th>name</th>
                    <th>title</th>
                    <th>isRoute</th>
                    <th>directory</th>
                    <th>npp</th>
                    <th>menuOn</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($articles as $a)
                    <tr>
                        <td>{{ $a->id }}</td>
                        <td>{{ $a->parentId }}</td>
                        <td><a href="/a_dmin/artEditor#{{ $a->id }}">{{ $a->name }}</a></td>
                        <td>{{ $a->title }}</td>
                        <td>{{ $a->isRoute ? 1 : 0 }}</td>
                        <td>{{ $a->directory ? 1 : 0 }}</td>
                        <td>{{ $a->npp }}</td>
                        <td>{{ $a->menuOn ? 1 : 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection
