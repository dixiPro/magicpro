@if($showForm)
    @include('magic::authForm', [
        'email' => $params['email'] ?? '',
        'password' => $params['password'] ?? '',
        'back' => $params['back'] ?? '/testSite',
    ])

    {{ $params['errorMsg'] ?? 'Неизвестная ошибка' }}
@endif

@include('partials.header')

@each('views.item', $items, 'item', 'views.empty')
