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
    <script src="/vendor/magicpro/prettier/postcss.js"></script>
    <script src="/vendor/magicpro/prettier/babel.js"></script>
    <script src="/vendor/magicpro/prettier/estree.js"></script>
</head>

<body>
    @mproauth
        <div class="d-flex flex-column">
            <div class="bg-primary py-2">
                <div class="d-flex flex-wrap justify-content-center m-0 p-0">
                    <div class="px-2">
                        <a class="text-white" href="/">@magic_msg('site')</a>
                    </div>
                    <div class="px-2">
                        <a class="text-white" href="/a_dmin/">@magic_msg('title')</a>
                    </div>
                    <div class="px-2">
                        <a class="text-white" href="/a_dmin/artEditor#1">@magic_msg('root')</a>
                    </div>
                    <div class="px-2">
                        <a class="text-white" href="/a_dmin/artList">@magic_msg('articles')</a>
                    </div>
                    <div class="px-2">
                        <a class="text-white" href="/a_dmin/adminList">@magic_msg('admins')</a>
                    </div>
                    <div class="px-2">
                        <a class="text-white" href="/a_dmin/crawler">@magic_msg('crawler')</a>
                    </div>
                    {{-- <div class="px-2"><a class="text-white" href="/a_dmin/fileEditor">FileEditor</a></div> --}}
                    <div class="px-2">
                        <a class="text-white" href="/f_ilament">@magic_msg('tables')</a>
                    </div>
                    <div class="px-2">
                        <a href="{{ route('magic.logout') }}" type="submit" class="btn btn-sm btn-success">
                            @magic_msg('logout')
                        </a>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-grow-1" id="admin-content">
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
    @else
        <div class="container my-5">
            <h1>MagicPro</h1>

            @if (session('mpro_error'))
                <div style="color:red">{{ session('mpro_error') }}</div>
            @endif

            <form method="POST" action="{{ route('magic.login') }}">
                @csrf

                <input type="text" name="email" placeholder="Email" required value="">
                <input type="password" name="password" placeholder="@magic_msg('password')" required value="">

                <label>
                    <input type="checkbox" name="remember"> @magic_msg('remember_me')
                </label>

                <button type="submit">@magic_msg('login')</button>
            </form>
        </div>
    @endmproauth

    <script src="/vendor/magicpro/bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>

</html>
