@guest
<x-auth-session-status class="mb-4" :status="session('status')" />

<x-input-error :messages="$errors->get('email')" class="mt-2" />

@php
session(['url.intended' => $urlRedirect]);
@endphp

<form method="POST" action="{{ route('login') }}">
    @csrf
    <input type="email" name="email" placeholder="Email" value="" required>
    <input type="password" name="password" placeholder="Пароль" value="" required>
    <label style="display:block; margin:8px 0;">
            <input type="checkbox" name="remember">
            Запомнить меня
        </label>
    <button class="btn btn-primary" type="submit">Войти</button>
</form>
@endguest
@auth
<div>Вы навторизованы</div>
@endauth