@php
    $menu = [
        ['route' => 'magic.a_dmin', 'label' => 'title', 'icon' => 'fas fa-crown'],
        ['route' => null, 'label' => 'site', 'icon' => 'fas fa-globe', 'url' => '/'],
        ['route' => 'magic.artEditor', 'label' => 'root', 'icon' => 'fas fa-edit'],
        ['route' => 'magic.start', 'label' => 'start', 'icon' => 'fas fa-play'],
        ['route' => 'magic.fileEditor', 'label' => 'file_manager', 'icon' => 'fas fa-cogs'],
        ['route' => 'magic.setup', 'label' => 'setup', 'icon' => 'fas fa-cogs'],
        ['route' => 'magic.import_tab', 'label' => 'import_tab', 'icon' => 'fas fa-file-import'],
        ['route' => 'magic.export_tab', 'label' => 'export_tab', 'icon' => 'fas fa-file-export'],
        ['route' => 'magic.admin_list', 'label' => 'admins', 'icon' => 'fas fa-users-cog'],
        ['route' => 'magic.crawler', 'label' => 'crawler', 'icon' => 'fas fa-spider'],
    ];

    $current = Route::currentRouteName();
@endphp

<ul class="list-group list-group-flush  text-nowrap">
    @foreach ($menu as $item)
        @if ($item['route'] === $current)
            <li class="list-group-item fw-bold">
                <i class="{{ $item['icon'] }}"></i>
                @magic_msg($item['label'])
            </li>
        @else
            <a href="{{ $item['route'] ? route($item['route']) : $item['url'] ?? '#' }}"
                class="list-group-item list-group-item-action">
                <i class="{{ $item['icon'] }}"></i>
                @magic_msg($item['label'])
            </a>
        @endif
    @endforeach
</ul>
