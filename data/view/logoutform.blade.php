<div>
@auth
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="btn btn-success" type="submit">Выйти</button>
</form>
@endauth
@guest
    <div>Вы не авторизованы</div>
@endguest
</div>