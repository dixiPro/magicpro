@php($GLOBALS['wide'] = 'middle')

@extends('magicAdmin::templateAdmin')

@section('title', 'MCP')

@section('body')
    @if (Auth::guard('magic')->user()?->role === 'admin')
        <div id="mcpToken"></div>
        @vite('admin/js/mcpToken.js', 'vendor/dixipro/magicpro')

        {{-- папка агента для Windows: лаунчеры, конфиги с адресом сайта, правила агента --}}
        <h5 class="mt-5">@magic_msg('mcp_install')</h5>
        @if (\MagicProSrc\Mcp\InstallArchive::available())
            <p class="mb-1">@magic_msg('mcp_install_hint')</p>
            <a class="btn btn-sm btn-primary" href="{{ route('magic.mcpInstall') }}">
                <i class="fas fa-download"></i> install.zip
            </a>
        @else
            <p class="text-danger">@magic_msg('mcp_install_no_zip')</p>
        @endif

        {{-- что агент может звать: имя и первое предложение описания из самого инструмента --}}
        <h5 class="mt-5">@magic_msg('mcp_tools')</h5>

        <table class="table table-striped table-sm">
            <tbody>
                @foreach (\MagicProSrc\Mcp\Servers\MagicProServer::toolList() as $tool)
                    <tr>
                        <td class="text-nowrap"><code>{{ $tool['name'] }}</code></td>
                        <td>{{ $tool['summary'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
