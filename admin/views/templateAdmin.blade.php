<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="/vendor/bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/vendor/fontawesome-free/css/all.min.css" />
    <script src="/vendor/emmet/emmet.js"></script>

    <script src="https://unpkg.com/prettier@3.2.5/standalone.js"></script>
    <script src="https://unpkg.com/prettier@3.2.5/plugins/html.js"></script>
    <script src="https://unpkg.com/@prettier/plugin-php@0.22.2/standalone.js"></script>


    {{-- 
    <script src="https://unpkg.com/prettier-plugin-php@0.22.2/standalone.js"></script> --}}
    {{-- <script src="https://unpkg.com/prettier-plugin-blade@2.1.0/dist/plugin.cjs"></script> --}}

</head>

<body>

    {{-- @php
        $guard = Auth::guard('magic');
        $guard->setUser((object) ['id' => 1, 'name' => 'Magic']); // любой объект
    @endphp --}}

    <div class="d-flex flex-column">
        <div class="bg-primary py-2">
            <div class="d-flex flex-wrap justify-content-center m-0 p-0">
                <div class="px-2"><a class="text-white" href="/">Сайт</a></div>
                <div class="px-2"><a class="text-white" href="/a_dmin/">Админка</a></div>
                <div class="px-2"><a class="text-white" href="/a_dmin/artEditor#1">Рут</a></div>
                <div class="px-2"><a class="text-white" href="/a_dmin/artList">Статьи</a></div>
                <div class="px-2"><a class="text-white" href="/a_dmin/adminList">Админы</a></div>
                <div class="px-2"><a class="text-white" href="/a_dminMunShine">Таблицы</a></div>
                <div class="px-2">
                    <a href="{{ route('magic.logout') }}" type="submit" class="btn btn-sm btn-success">Выйти</a>
                </div>
            </div>
        </div>

        @if (Auth::guard('magic')->check())
            Пользователь: {{ Auth::guard('magic')->user()->name }}
        @else
            Не авторизован
        @endif

        <!-- Второй див, растягивается до конца страницы -->
        <div class="d-flex flex-column flex-grow-1" id='admin-content'>
            @if ($GLOBALS['wide'] ?? '' == 'middle')
                <div class="container">
                    @yield('body')
                </div>
            @else
                @yield('body')
            @endif
        </div>
    </div>

    @hasSection('script')
        @yield('script')
    @endif

    {{-- не админ --}}

    <div class="container my-5">
        @if (session('mpro_error'))
            <div style="color:red">{{ session('mpro_error') }}</div>
        @endif
        войти
        <form method="POST" action="{{ route('magic.login') }}">
            @csrf
            <input type="email" name="email" placeholder="Email" required value="a@a.a">
            <input type="password" name="password" placeholder="Пароль" required value="magic">
            <label>
                <input type="checkbox" name="remember"> Запомнить меня
            </label>
            <button type="submit">Войти</button>
        </form>
    </div>


    {{-- @mproauth
    @else
    @endmproauth --}}


    {{-- @vite(['resources/js/adminCommon.js']) --}}

</body>

</html>
