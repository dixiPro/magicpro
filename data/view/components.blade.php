@extends('magic::root')

@section('body')
    <h1>Компоненты</h1>

    <h3>top_menu без параметра</h3>
    <x-magic::top_menu></x-magic::top_menu>

    <h3>top_menu с параметром</h3>
    <x-magic::top_menu :nameArt="'topMenu'"></x-magic::top_menu>

    <h3>Авторизация liveWare</h3>

    <livewire:magic::loginware/>


    <h3>Авторизация через форму</h3>

    @auth
    <div>
        <span>Вы вошли как: {{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
    </div>    
    <div class="my-2">
        <x-magic::logoutform></x-magic::logoutform>
    </div>    
    @endauth
    @guest
        <div>Вы не авторизованы</div>
        <div class="my-2">
            <x-magic::loginform :urlRedirect="'/' . $Env['name']" />
        </div>
    @endguest

@endsection