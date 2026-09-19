<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- токен формы для API админки: фронт кладёт его в заголовок X-CSRF-TOKEN --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="/vendor/dixipro/magicpro/bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/vendor/dixipro/magicpro/fontawesome-free/css/all.min.css" />

    <script src="/vendor/dixipro/magicpro/emmet/emmet.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/standalone.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/plugin-html.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/plugin-php.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/postcss.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/babel.js"></script>
    <script src="/vendor/dixipro/magicpro/prettier/estree.js"></script>

    <style>
        html {
            font-size: 15px;
        }
    </style>
</head>

<body>
    @mproauth
        <div class="d-flex flex-column">
            <div class="bg-primary py-2">
                <div class="d-flex flex-wrap justify-content-center m-0 p-0">
                    <div class="px-2"><a class="text-white" href="/">@magic_msg('site')</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/">@magic_msg('title')</a></div>
                    <div class="px-2"><a class="text-white" href="/a_dmin/artEditor#1">@magic_msg('root')</a></div>
                    <div class="px-2">
                        <form method="POST" action="{{ route('magic.logout') }}" class="m-0">@csrf
                            <button type="submit" class="btn btn-sm btn-success">@magic_msg('logout')</button>
                        </form>
                    </div>
                </div>
            </div>
            @if ($GLOBALS['nolfetMenu'] ?? false == true)
                @yield('body') @hasSection('script')
                    @yield('script')
                @endif
            @else
                <div class="d-flex">
                    <div class="px-3">@include('magicAdmin::leftColumn') </div>
                    <div class="flex-grow-1 mx-3">@yield('body') @hasSection('script')
                            @yield('script')
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @else
            {{-- вход в админку: карточка по центру экрана --}}
            <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light px-3">
                <div class="card shadow-sm w-100" style="max-width: 380px">
                    <div class="card-body p-4">
                        <h1 class="h4 text-center mb-4">
                            <i class="fas fa-magic me-2 text-primary"></i>MagicPro
                        </h1>

                        @if (session('mpro_error'))
                            <div class="alert alert-danger py-2 small">{{ session('mpro_error') }}</div>
                        @endif

                        <form method="POST" action="{{ route('magic.login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="mproEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="mproEmail" name="email"
                                    autocomplete="username" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="mproPassword" class="form-label">@magic_msg('password')</label>
                                <input type="password" class="form-control" id="mproPassword" name="password"
                                    autocomplete="current-password" required>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="mproRemember" name="remember">
                                <label class="form-check-label" for="mproRemember">@magic_msg('remember_me')</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">@magic_msg('login')</button>
                        </form>
                    </div>
                </div>
            </div>
        @endmproauth
        <script src="/vendor/dixipro/magicpro/bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>

</html>
