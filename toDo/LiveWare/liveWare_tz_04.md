# ТЗ v4: Livewire в MagicPro

Основа — `liveWare_tz_03.md` с ответами владельца.

## Решено

1. **Базовый класс `MagicProLivewire`** в `src/Livewire/`, пространство
   `MagicProSrc\Livewire`, наследник `Livewire\Component`. Общий `render()`:

   ```php
   return view('magic::' . class_basename(static::class));
   ```

2. **Компонент статьи наследуется от `MagicProLivewire` и `render()` не
   пишет**, если не нужна своя логика. Публичные свойства видны в блейде сами.
3. **Подключение только тегом** `<livewire:magic::имя />`.
4. **Реестр `LivewireComponentRegistry` остаётся** и переезжает в
   `src/Livewire/`: он переводит `magic::имя` в `MagicProControllers\имя` на
   каждом запросе `/livewire/update`. Ветка старого имени
   `magic-pro-controllers.имя` удаляется.
5. **Старый Livewire удаляется целиком**, совместимости нет: на боевых
   серверах Livewire не используется.
6. **Переключатель «Livewire-компонент» в редакторе статьи.** Включил — статья
   сама настроилась как компонент. Ничего не вставляет в текст и ничего не
   проверяет при сохранении: накосячил админ — его забота, как с `postEnable`.
7. **Флаг хранится в `routeParams`**: ключ `livewire` (`true` / `false`).
8. **Кнопка «Livewire контроллер»** в автодополнении редактора остаётся —
   она подставляет заготовку.
9. **Кнопка и команда API** переименовываются: «LiveWare» → «Livewire».
10. **Тестовые статьи** 288, 289, 355–358 — решаем после того, как система
    готова.

## Как это работает

Статья `counter`: переключатель «Livewire-компонент» включён, кнопкой
«Livewire контроллер» подставлена заготовка. Контроллер:

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

Блейд:

```blade
<div>
  <button type="button" wire:click="add">+</button>
  {{ $count }}
</div>
```

Билдер при сохранении подменяет `Magic_Pro_Name_Controller` именем статьи:
класс называется `counter`, `class_basename(static::class)` отдаёт `counter`,
компонент рисует `magic::counter` — блейд своей же статьи. Переименование
статьи ничего не ломает.

Вызов с любой страницы: `<livewire:magic::counter />`.

## Что сделать

### Новое

| Где                                                      | Что                                                                                |
| -------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `src/Livewire/MagicProLivewire.php`                      | базовый класс с `render()`                                                         |
| `src/Livewire/LivewireComponentRegistry.php`             | перенос из `src/`, только ветка `magic::`                                          |
| `MagicServiceProvider`                                   | реестр из нового места                                                             |
| `admin/controller/default/defaultControllerLivewire.php` | заготовка: наследник `MagicProLivewire`, одно свойство, один метод, без `render()` |
| `Article::ROUTE_PARAMS`                                  | ключ `livewire` со значением `false` по умолчанию                                  |
| Редактор статьи, параметры маршрута                      | переключатель «Livewire-компонент»                                                 |
| `translate.js`                                           | подпись переключателя                                                              |

### Удалить / переименовать

| Где                                                   | Что                                                                          |
| ----------------------------------------------------- | ---------------------------------------------------------------------------- |
| `src/LivewireComponentRegistry.php`                   | уходит, класс переехал в `src/Livewire/`                                     |
| `LivewireComponentRegistry`                           | ветка `magic-pro-controllers.имя`                                            |
| `defaultControllerLivewire.php`                       | `extends Component`, свой `render()` с `view('magic::lvcomponent')`          |
| `MagicProBuilder.php`                                 | `readDefaultLiveWareController` → `readDefaultLivewireController`            |
| `API_ArticlesPostController`                          | команда `getDefaultLiveWareController` → `getDefaultLivewireController`      |
| `admin/js/app/ArtEditor/store.js`, `Autocomplete.vue` | `getLiveWareController` → `getLivewireController`                            |
| `translate.js`                                        | ключ `autocomplete_liveWare_controller` → `autocomplete_livewire_controller` |
| `toDo/risks/editor.md`, п. 1                          | риск «заготовка привязана к `lvcomponent`» снимается                         |

### Дока

- `docs/ru/mainUse/use.md`, раздел «Livewire-компонент» — переписать:
  переключатель, заготовка, базовый класс, вызов тегом, свойства в блейде,
  когда нужен свой `render()`. Флаг `livewire` — в таблицу `routeParams`.
- `docs/ru/mainDev/use.md` — карта файлов, реестр, команда API.
- `docs/ru/mainDev/diagnostics.md` — проверка Livewire под новое устройство.

## Вопросы

1. **Что именно выставляет переключатель.** «Всё настроилось как надо» —
   это: маршрут `isRoute` выключен, контроллер `useController` включён? Или
   ещё что-то? И при выключении переключателя — возвращать как было или
   ничего не трогать?
   > да поставил галку Livewire компонент, `isRoute` выключен, контроллер `useController` включён.
   > И кроме этой галки больше нет ничего в настройках раута.
   > отжал галку настройки раута появились.
   > и ничео не проверять, если админ хочет нагадить он найдет как.
2. **МСП.** Инструмент `save-article` перечисляет ключи `routeParams` явно.
   Добавить туда `livewire`, чтобы агент мог делать компоненты сам?
   > обязательно и в доку ссылку на лайваре
