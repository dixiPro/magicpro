# ТЗ v2: Livewire в MagicPro

Основа — `liveWare_tz_01.md` и ответы владельца.

## Решено

1. **Базовый класс** для всех Livewire-компонентов статей, наследник
   `Livewire\Component`. В нём общий `render()`:

   ```php
   return view('magic::' . class_basename(static::class));
   ```

2. **Компонент статьи наследуется от базы и `render()` не пишет**, если не
   нужна своя логика. Публичные свойства видны в блейде сами.
3. **Старое не поддерживаем.** На боевых серверах Livewire не используется,
   поэтому совместимости нет: всё старое вычищается, остаётся только новое.

## Как это работает

Билдер при сохранении подменяет `Magic_Pro_Name_Controller` именем статьи.
Статья `counter`:

```php
namespace MagicProControllers;

use MagicProSrc\Livewire\MagicProLivewire;

class Magic_Pro_Name_Controller extends MagicProLivewire
{
    public int $count = 0;

    public function add(): void
    {
        $this->count++;
    }
}
```

После сохранения класс называется `counter`, `class_basename(static::class)`
отдаёт `counter`, и компонент рисует `magic::counter` — блейд своей же статьи.
Имя блейда нигде не пишется руками, переименование статьи ничего не ломает.

Вызов со страницы: `<livewire:magic::counter />`.

## Что сделать

### Новое

| Где                                                      | Что                                                                  |
| -------------------------------------------------------- | -------------------------------------------------------------------- |
| `src/Livewire/MagicProLivewire.php`                      | базовый класс с `render()`                                           |
| `src/Livewire/LivewireComponentRegistry.php`             | перенос из `src/`; `magic::имя` → `MagicProControllers\имя`          |
| `admin/controller/default/defaultControllerLivewire.php` | заготовка: наследник базы, одно свойство, один метод, без `render()` |

### Вычистить

| Где                                                                                                    | Что уходит                                                             |
| ------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------- |
| `LivewireComponentRegistry`                                                                            | ветка старого префикса `magic-pro-controllers.имя`                     |
| `defaultControllerLivewire.php`                                                                        | `extends Component`, `render()` с жёстким `view('magic::lvcomponent')` |
| `docs/ru/mainUse/use.md`, раздел «Livewire-компонент»                                                  | пример с `lvcomponent` и совет «поменяйте строку `view()`»             |
| `docs/ru/mainDev/use.md`                                                                               | пути и описание реестра под новое место                                |
| `docs/ru/mainDev/diagnostics.md`                                                                       | проверка Livewire под новое устройство                                 |
| `toDo/risks/editor.md`, п. 1                                                                           | риск «заготовка привязана к `lvcomponent`» снимается                   |
| Статьи тестового сайта 288 `tLiveVare`, 289 `livecomponent`, 355–358 (`live_counter*`, `live_simple*`) | переделать на базу или удалить — см. вопросы                           |

### Дока

Раздел Livewire в `docs/ru/mainUse/use.md` переписать под новое: база, статья
без маршрута, вызов тегом, свойства в блейде, когда нужен свой `render()`.

## Вопросы

1. **Имя базового класса.** В черновике `MagicProLiveWare`. Правильное
   написание библиотеки — Livewire, в пакете уже есть `LivewireComponentRegistry`.
   Назвать `MagicProLivewire`?
   > да
2. **Место.** `src/Livewire/` вместе с реестром, пространство
   `MagicProSrc\Livewire` — так?
   > да
3. **Маршрут у Livewire-статьи.** У такого класса нет `handle()`, и если у
   статьи включить `isRoute`, её адрес падает с ошибкой. Отказывать при
   сохранении («Livewire-компонент не может быть страницей»), или оставить на
   совести автора?
   > надо добавлять кнопочку в админку, компонент лайваре
4. **Тестовые статьи** 288, 289, 355–358: переделать на новую базу (один
   пример в справке) или удалить и сделать заново?
   > решим после создания системы
5. **Кнопка в редакторе** называется «LiveWare контроллер», команда API —
   `getDefaultLiveWareController`. Переименовать в Livewire заодно с чисткой?

   > да

6. Старый лайваре удаляем
