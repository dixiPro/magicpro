@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') {{ MAGIC_VERSION }}
@endsection

@section('body')
    <h1>@magic_msg('title') {{ MAGIC_VERSION }}</h1>

    {{-- что проверено и что из этого починилось при заходе --}}
    <div class="mt-4">
        <div><strong>@magic_msg('diagnostics_title')</strong></div>

        @foreach ($steps as $step)
            <div>
                <i class="fas {{ $step['ok'] ? 'fa-check text-success' : 'fa-times text-danger' }}"></i>
                {{ $step['title'] }}
                <small class="text-muted">{{ $step['note'] }}</small>
                @if ($step['hint'])
                    <small class="text-danger">— {{ $step['hint'] }}</small>
                @endif
            </div>
        @endforeach
    </div>

    {{-- чем можно резать изображения: утилиты внешние, без них ресайз молчит --}}
    <div class="mt-4">
        <div>
            <strong>@magic_msg('image_tools')</strong>
        </div>

        @foreach ($tools as $tool)
            <div>
                <i class="fas {{ $tool['ok'] ? 'fa-check text-success' : 'fa-times text-danger' }}"></i>
                {{ $tool['name'] }}
                @if ($tool['ok'])
                    <small class="text-muted">{{ $tool['version'] }}</small>
                @else
                    <small class="text-muted">@magic_msg('tool_not_found')</small>
                    <code class="small ms-1">{{ $tool['hint'] }}</code>
                @endif
            </div>
        @endforeach
    </div>

    {{-- планировщик: крон снаружи, видно его только по отметке --}}
    <div class="mt-4">
        <div>
            <strong>@magic_msg('cron_title')</strong>
        </div>

        <div>
            <i class="fas {{ $cron['ok'] ? 'fa-check text-success' : 'fa-times text-danger' }}"></i>
            @if ($cron['ok'])
                <small class="text-muted">{{ $cron['note'] }}</small>
            @else
                <span class="text-danger">{{ $cron['note'] }}</span>
            @endif
        </div>

        @if (!$cron['ok'])
            <div class="mt-1">
                <small class="text-danger">@magic_msg('cron_fix')</small>
                <div><code class="small text-break">{{ $cron['command'] }}</code></div>
            </div>
        @endif
    </div>

    <div class="mt-4">@magic_msg('current_user')</div>
    @php
        MproHelper::dump(Auth::guard('magic')->user());
    @endphp
@endsection
