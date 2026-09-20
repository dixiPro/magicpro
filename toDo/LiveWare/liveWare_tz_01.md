# liveWare magicPro

сейчас лайваре работает, но все сильно не прозрачно.

Предлагаю

Создать базовый класс `MagicProLiveWare extends Livewire\Component`.

В нём реализовать общий метод `render()`, который автоматически определяет имя дочернего класса через `class_basename(static::class)` и подключает Blade-шаблон:

```php
return view('magic::' . class_basename(static::class));
```

Все рабочие Livewire-компоненты должны наследоваться от `MagicProLiveWare` и не содержать собственный `render()`, если не требуется отдельная логика.

Пример использования:

```php
class Magic_Pro_Name_Controller extends MagicProLiveWare
{
    public int $count = 0;

    public function add(): void
    {
        $this->count++;
    }
}
```

Для `Magic_Pro_Name_Controller` автоматически будет подключён шаблон:

```php
magic::Magic_Pro_Name_Controller
```

Что думаешь?
