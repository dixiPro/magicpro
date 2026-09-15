@php
    $GLOBALS['wide'] = 'middle';

    use MagicProSrc\Docs\DocsTree;

    // язык страницы — язык админки: отдельного переключателя нет, чтобы дока и
    // подписи вокруг неё не расходились
    $lang = DocsTree::lang();
    $tree = DocsTree::tree($lang);

    // три уровня одной страницей: список разделов, список страниц раздела,
    // сама страница. Что показать, решают два параметра адреса
    $page = (string) request()->query('p', '');
    $group = request()->query('g', null);
    $group = is_numeric($group) && isset($tree[(int) $group]) ? (int) $group : null;

    // пришли сразу на страницу — раздел находится по ней, чтобы дорожка наверху
    // вела обратно в него
    if ($page !== '' && $group === null) {
        foreach ($tree as $i => $row) {
            foreach ($row['group'] ?? [] as $item) {
                if (($item['link'] ?? '') === $page) {
                    $group = $i;
                }
            }
        }
    }

    $html = $page !== '' ? DocsTree::page($lang, $page) : '';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('documentation')
@endsection

@section('body')
    {{-- markdown приезжает голым: таблицам и коду нужен вид --}}
    <style>
        .magicDoc table {
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .magicDoc th,
        .magicDoc td {
            border: 1px solid var(--bs-border-color);
            padding: 0.3rem 0.6rem;
            vertical-align: top;
        }

        .magicDoc pre {
            background: var(--bs-tertiary-bg);
            padding: 0.6rem 0.8rem;
            border-radius: 0.3rem;
            overflow-x: auto;
        }

        .magicDoc h2,
        .magicDoc h3 {
            margin-top: 1.5rem;
        }

        .docList a {
            font-size: 1.1rem;
        }
    </style>

    @if (!$tree)
        <h1>@magic_msg('documentation')</h1>
        <div class="alert alert-warning">@magic_msg('documentation_no_lang') <code>{{ $lang }}</code></div>
    @elseif ($group === null)
        {{-- первый уровень: только разделы --}}
        <h1>{{ DocsTree::title($lang) }}</h1>

        <ul class="list-unstyled docList mt-3">
            @foreach ($tree as $i => $row)
                <li class="mb-2"><a href="?g={{ $i }}">{{ $row['name'] ?? '' }}</a></li>
            @endforeach
        </ul>

        @php($orphans = DocsTree::orphans($lang))

        {{-- файлы, которых нет в index.json: на экране их иначе не видно --}}
        @if ($orphans)
            <h2 class="h5 mt-4">@magic_msg('documentation_orphans')</h2>

            <ul class="list-unstyled small">
                @foreach ($orphans as $row)
                    <li class="mb-1">
                        @if ($row['readable'])
                            <a href="?p={{ $row['link'] }}">{{ $row['link'] }}</a>
                        @else
                            <span class="text-muted">{{ $row['link'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @elseif ($page === '')
        {{-- второй уровень: страницы раздела --}}
        <div class="mb-2"><a href="{{ route('magic.documentation') }}" title="@magic_msg('documentation')">🏠</a></div>

        <h1>{{ $tree[$group]['name'] ?? '' }}</h1>

        <ul class="list-unstyled docList mt-3">
            @foreach ($tree[$group]['group'] ?? [] as $row)
                <li class="mb-2">
                    @if ($row['exists'])
                        <a href="?g={{ $group }}&p={{ $row['link'] }}">{{ $row['name'] }}</a>
                        @if (! empty($row['about']))
                            <div class="small text-muted">{{ $row['about'] }}</div>
                        @endif
                    @else
                        {{-- файла на этом языке нет: строка остаётся, чтобы был виден раздел --}}
                        <span class="text-muted">{{ $row['name'] }} — @magic_msg('documentation_none')</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        {{-- третий уровень: сама страница --}}
        <div class="mb-2 small">
            <a href="{{ route('magic.documentation') }}" title="@magic_msg('documentation')">🏠</a>
            <a href="?g={{ $group }}" class="ms-2">{{ $tree[$group]['name'] ?? '' }}</a>
        </div>

        @if ($html !== '')
            <div class="mb-3">
                <a href="{{ route('magic.documentationFile') }}?p={{ $page }}">@magic_msg('documentation_download')</a>
            </div>

            <div class="magicDoc">{!! $html !!}</div>
        @else
            <div class="alert alert-warning">@magic_msg('documentation_none')</div>
        @endif
    @endif
@endsection
