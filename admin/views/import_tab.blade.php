@php
    $GLOBALS['wide'] = 'middle';
@endphp

@extends('magicAdmin::templateAdmin')

@section('title')
    @magic_msg('title') @magic_msg('import_tab')
@endsection

@section('body')
    <h1>@magic_msg('import_tab')</h1>

    <div class="my-3">
        <div class="my-2"><strong>@magic_msg('import')</strong></div>
        <form action="/a_dmin/importArticle" method="POST" enctype="multipart/form-data" class="p-3 border rounded bg-light">
            @csrf

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">@magic_msg('select_json_file')</label>
                <input type="file" name="file" id="file" accept=".json, .xml" class="form-control" required>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="writeBase" value="1" id="writeBase">
                <label class="form-check-label" for="writeBase">
                    @magic_msg('save_changes_to_db')
                </label>
                <div class="form-text">@magic_msg('import_check_only_hint')</div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="typeFile" checked id="typeFileJson" value="json"
                        required>
                    <label class="form-check-label" for="typeFileJson">
                        @magic_msg('from_json')
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="typeFile" id="typeFileXml" value="xml">
                    <label class="form-check-label" for="typeFileXml">
                        @magic_msg('from_xml')
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">@magic_msg('import')</button>
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



@endsection
