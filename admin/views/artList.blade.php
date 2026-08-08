@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('articles_list')
@endsection

@section('body')
    <h2>@magic_msg('articles_list')</h2>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($articles->total() === 0)
        <p>@magic_msg('no_records')</p>
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
                    <th>last</th>
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
                        <td><small>{{ $a->updated_at?->format('d.m.y H:i') }}</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- своя разметка страниц: links() отдаёт tailwind, а админка на bootstrap --}}
        @if ($articles->hasPages())
            <nav>
                <ul class="pagination pagination-sm">
                    <li class="page-item @if ($articles->onFirstPage()) disabled @endif">
                        <a class="page-link" href="{{ $articles->previousPageUrl() }}">&laquo;</a>
                    </li>

                    @foreach ($articles->getUrlRange(1, $articles->lastPage()) as $number => $url)
                        <li class="page-item @if ($number == $articles->currentPage()) active @endif">
                            <a class="page-link" href="{{ $url }}">{{ $number }}</a>
                        </li>
                    @endforeach

                    <li class="page-item @if (!$articles->hasMorePages()) disabled @endif">
                        <a class="page-link" href="{{ $articles->nextPageUrl() }}">&raquo;</a>
                    </li>
                </ul>
            </nav>
        @endif
    @endif

@endsection
