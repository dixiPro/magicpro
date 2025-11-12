<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="/vendor/magicpro/bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/vendor/magicpro/fontawesome-free/css/all.min.css" />

    <script src="/vendor/magicpro/emmet/emmet.js"></script>
    <script src="/vendor/magicpro/prettier/standalone.js"></script>
    <script src="/vendor/magicpro/prettier/plugin-html.js"></script>
    <script src="/vendor/magicpro/prettier/plugin-php.js"></script>


</head>

<body>
    @mproauth
        <div class="d-flex flex-column">
            <div class="bg-primary py-2">
                <div class="d-flex flex-wrap justify-content-center m-0 p-0">
                    <div class="px-2"><a class="text-white" href="/">Сайт</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/">Админка</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/artEditor#1">Рут</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/artList">Статьи</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/adminList">Админы</a></div>
                    <div class="px-2"><a class="text-white" href="/f_ilament">Таблицы</a></div>
                    <div class="px-2">
                        <a href="{{ route('magic.logout') }}" type="submit" class="btn btn-sm btn-success">Выйти</a>
                    </div>
                </div>
            </div>

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

        {{-- @if (Auth::guard('magic')->check())
            Пользователь: {{ Auth::guard('magic')->user()->name }}
        @else
            Не авторизован
        @endif --}}

        {{-- не админ --}}
    @else
        <div class="container my-5">
            <h1>MagicPro</h1>
            @if (session('mpro_error'))
                <div style="color:red">{{ session('mpro_error') }}</div>
            @endif

            <form method="POST" action="{{ route('magic.login') }}">
                @csrf
                <input type="text" name="email" placeholder="Email" required value="">
                <input type="password" name="password" placeholder="Пароль" required value="">

                <label>
                    <input type="checkbox" name="remember"> Запомнить меня
                </label>
                <button type="submit">Войти</button>
            </form>
        </div>
    @endmproauth


    {{-- @vite(['resources/js/adminCommon.js']) --}}

</body>

</html>
