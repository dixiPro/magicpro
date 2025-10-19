<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? $Env['title']}}</title>
    <link rel="stylesheet" href="/vendor/bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/vendor/fontawesome-free/css/all.min.css" />
    @livewireStyles
</head>

<body>

    <!-- Первый div - занимает столько, сколько нужно -->
    <div class="container-fluid p-1 " style="background: #ddd">
        <div class="text-center">
            <h3>Шапочка</h3>
        </div>
    </div>
    <div class="container">
        @yield('body')
    </div>

<div class="container">
<hr>
<div>Подвал</div>
<div>$ENV</div>
    <pre>{{ print_r($Env, true) }}</pre>
</div> 

@livewireScripts
</body>

</html>