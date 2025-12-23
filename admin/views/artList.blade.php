@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('articles_list')
@endsection

@section('body')
    <h2>Список статей</h2>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($articles->isEmpty())
        <p>Записей нет.</p>
    @else
        <table class="table table-striped  table-sm">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>name</th>
                    <th>title</th>
                    <th>id</th>
                    <th>parent</th>
                    <th>npp</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $getMsg = fn($key) => \MagicProSrc\MagicLang::getMsg($key);
                @endphp
                @foreach ($articles as $a)
                    @php

                        $tst = $getMsg('has_controller');
                        $useController = $a->routeParams['useController'] ?? 'error';
                        $icon =
                            $useController === 'error'
                                ? '<i style="color:red" class="fas fa-exclamation-circle" title="' .
                                    $getMsg('controller_error') .
                                    '"></i>'
                                : ($useController
                                    ? '<i class="fas fa-cog" title="' . $getMsg('has_controller') . '" ></i>'
                                    : '');

                    @endphp
                    <tr>
                        <td style="width: 25px">{!! $icon !!}</td>
                        <td style="width: 25px">{!! $a->isRoute ? '<i class="icon-small fa-link fas mx-1"></i>' : '' !!}</td>
                        <td style="width: 25px">{!! $a->menuOn ? '<i class="icon-small fas fa-eye mx-1"></i>' : '' !!}</td>
                        <td style="width: 25px">{!! $a->directory ? '<i class="icon-small fas fa-folder mx-1"></i>' : '' !!}</td>
                        <td><a target="_blank" href="/a_dmin/artEditor#{{ $a->id }}">{{ $a->name }}</a></td>
                        </td>
                        <td>{{ $a->title }}
                        <td>{{ $a->id }}</td>
                        <td>{{ $a->parentId }}</td>
                        <td>{{ $a->npp }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection
