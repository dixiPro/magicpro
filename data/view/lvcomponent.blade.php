<div>
    <input type="text" wire:model.live="inputText" placeholder="Введите текст...">

    <p>Вы ввели: <strong>{{ $text }}</strong></p>

<div>Передано как параметр
    {{ $title }}
</div>

<div wire:loading>
    Загружаем...
</div>
</div>