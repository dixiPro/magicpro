@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') {{ MAGIC_VERSION }}
@endsection

@section('body')
    <h1>@magic_msg('title') {{ MAGIC_VERSION }}</h1>

    {{-- беды: пусто, когда всё на месте --}}
    @if ($report)
        <div class="mt-4">
            <div><strong>@magic_msg('diagnostics_title')</strong></div>
            {!! $report !!}
        </div>
    @endif

    {{-- пройденные проверки: молчание отчётом не считается --}}
    @if ($okList)
        <div class="mt-4">
            <div><strong>@magic_msg('diagnostics_ok_title')</strong></div>
            <ul class="list-unstyled small mt-2">
                @foreach ($okList as $item)
                    <li>
                        <i class="fas fa-check text-success"></i>
                        {{ \MagicProSrc\MagicLang::getMsg($item['key']) }}
                        @if ($item['note'])
                            <span class="text-muted">— {{ $item['note'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4">@magic_msg('current_user')</div>
    @php
        MproHelper::dump(Auth::guard('magic')->user());
    @endphp
@endsection
