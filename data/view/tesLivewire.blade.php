@extends('magic::root')

@section('body')
    @if ($submitted)
    <p>Email принят!</p>
    @else
    <form wire:submit.prevent="submit">
        <input type="email" wire:model="email" placeholder="Введите ваш email">
                @error('email') <span>{{ $message }}</span> @enderror
        <button type="submit">Отправить</button>
    </form>
    @endif
@endsection