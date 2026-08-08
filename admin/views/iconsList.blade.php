{{--
    Иконки проектов в одном месте.

    Список собран поиском по коду MagicPro и MagicShop. Нужен при переезде на
    новую версию FontAwesome: после подмены css пропавшая иконка рисуется пустым
    местом, и по подписи сразу видно, какую именно искать.

    Файл только выводит массив, без @extends: вставляется куда угодно через
    @include('magicAdmin::iconsList').

    Добавили новую иконку в коде — допишите её сюда.
--}}

@php
    $icons = [
        'fa fa-circle',
        'fa fa-columns',
        'fa fa-indent',
        'far fa-edit',
        'far fa-file',
        'far fa-file-image',
        'far fa-folder',
        'fas fa-address-card',
        'fas fa-angle-left',
        'fas fa-angle-right',
        'fas fa-arrow-circle-down',
        'fas fa-arrow-circle-up',
        'fas fa-arrow-left',
        'fas fa-arrow-right',
        'fas fa-arrow-up',
        'fas fa-arrows-alt-v',
        'fas fa-ban',
        'fas fa-bars',
        'fas fa-check',
        'fas fa-chevron-left',
        'fas fa-chevron-right',
        'fas fa-cog',
        'fas fa-comment',
        'fas fa-copy',
        'fas fa-crown',
        'fas fa-cut',
        'fas fa-database',
        'fas fa-edit',
        'fas fa-ellipsis-h',
        'fas fa-envelope',
        'fas fa-exchange-alt',
        'fas fa-exclamation-circle',
        'fas fa-exclamation-triangle',
        'fas fa-external-link-alt',
        'fas fa-eye',
        'fas fa-eye-slash',
        'fas fa-file-export',
        'fas fa-file-import',
        'fas fa-file-upload',
        'fas fa-folder',
        'fas fa-folder-open',
        'fas fa-folder-plus',
        'fas fa-globe',
        'fas fa-grip-vertical',
        'fas fa-home',
        'fas fa-image',
        'fas fa-lightbulb',
        'fas fa-link',
        'fas fa-list',
        'fas fa-magic',
        'fas fa-paste',
        'fas fa-pen',
        'fas fa-phone',
        'fas fa-plus',
        'fas fa-plus-circle',
        'fas fa-question',
        'fas fa-question-circle',
        'fas fa-random',
        'fas fa-reply',
        'fas fa-search',
        'fas fa-server',
        'fas fa-shopping-basket',
        'fas fa-sign-in-alt',
        'fas fa-sitemap',
        'fas fa-spider',
        'fas fa-stop',
        'fas fa-stream',
        'fas fa-sync-alt',
        'fas fa-tasks',
        'fas fa-th',
        'fas fa-times',
        'fas fa-trash',
        'fas fa-user',
        'fas fa-users',
        'fas fa-users-cog',
        'fas fa-video',
    ];
@endphp

<div class="my-3">
    <h5 class="mb-3">FontAwesome: {{ count($icons) }}</h5>

    <div class="row row-cols-3 row-cols-md-6 row-cols-xl-8 g-3">
        @foreach ($icons as $icon)
            <div class="col">
                <div class="border rounded h-100 p-2 text-center">
                    <div class="fs-3"><i class="{{ $icon }}"></i></div>
                    <div class="small text-break">{{ $icon }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
