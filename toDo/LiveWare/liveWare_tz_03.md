# ТЗ v3: Livewire в MagicPro

Основа — `liveWare_tz_02.md` с ответами владельца.

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
   каждом запросе `/livewire/update`, без него первое же действие падает.
   Ветка старого имени `magic-pro-controllers.имя` удаляется.
5. **Старый Livewire удаляется целиком**, совместимости нет: на боевых
   серверах Livewire не используется.
6. **В админке — переключатель «Livewire-компонент»** у статьи.
7. **Кнопка редактора и команда API** переименовываются: «LiveWare» →
   «Livewire».
8. **Тестовые статьи** 288, 289, 355–358 — решаем после того, как система
   готова.

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

Блейд статьи `counter`:

```blade
<div>
  <button type="button" wire:click="add">+</button>
  {{ $count }}
</div>
```

После сохранения класс называется `counter`, `class_basename(static::class)`
отдаёт `counter`, компонент рисует `magic::counter` — блейд своей же статьи.
Имя блейда руками не пишется, переименование статьи ничего не ломает.

Вызов со страницы: `<livewire:magic::counter />`.

## Что сделать

### Новое

| Где                                                      | Что                                                                                |
| -------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `src/Livewire/MagicProLivewire.php`                      | базовый класс с `render()`                                                         |
| `src/Livewire/LivewireComponentRegistry.php`             | перенос из `src/`, только ветка `magic::`                                          |
| `MagicServiceProvider`                                   | подключение реестра из нового места                                                |
| `admin/controller/default/defaultControllerLivewire.php` | заготовка: наследник `MagicProLivewire`, одно свойство, один метод, без `render()` |
| Редактор статьи                                          | переключатель «Livewire-компонент» (см. вопросы)                                   |

### Удалить / переименовать

| Где                                                   | Что                                                                                                      |
| ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| `src/LivewireComponentRegistry.php`                   | уходит, класс переехал в `src/Livewire/`                                                                 |
| `LivewireComponentRegistry`                           | ветка `magic-pro-controllers.имя`                                                                        |
| `defaultControllerLivewire.php`                       | `extends Component`, свой `render()` с `view('magic::lvcomponent')`                                      |
| `MagicProBuilder.php`                                 | `readDefaultLiveWareController` → `readDefaultLivewireController`                                        |
| `API_ArticlesPostController`                          | команда `getDefaultLiveWareController` → `getDefaultLivewireController`                                  |
| `admin/js/app/ArtEditor/store.js`, `Autocomplete.vue` | `getLiveWareController` → `getLivewireController`                                                        |
| `translate.js`                                        | ключ `autocomplete_liveWare_controller` → `autocomplete_livewire_controller`, текст «Livewire-компонент» |
| `toDo/risks/editor.md`, п. 1                          | риск «заготовка привязана к `lvcomponent`» снимается                                                     |

### Дока

- `docs/ru/mainUse/use.md`, раздел «Livewire-компонент» — переписать: базовый
  класс, статья без маршрута, переключатель в админке, вызов тегом, свойства
  в блейде, когда нужен свой `render()`.
- `docs/ru/mainDev/use.md` — карта файлов, описание реестра, команда API.
- `docs/ru/mainDev/diagnostics.md` — проверка Livewire под новое устройство.

## Вопросы

1. **Что делает переключатель «Livewire-компонент».** Варианты, можно
   несколько:
   - выключает маршрут и не даёт его включить, пока переключатель стоит;
   - включает контроллер и вставляет Livewire-заготовку вместо обычной;
   - при сохранении проверяет, что класс наследует `MagicProLivewire`, иначе
     ошибка.
     > вставлять ничего не нужно, кнопку поставили все настроилось как надо. Если админ там чет накосячил его проблемы. Мы же не проверяем пост, например.
2. **Где он хранится.** Новый ключ в `routeParams` (например,
   `livewire: true`) рядом с `useController` — или его вообще не хранить, а
   вычислять по тексту контроллера (`extends MagicProLivewire`)?
   > ключ храним routeParams
3. **Кнопка «Livewire контроллер»** в автодополнении редактора остаётся
   рядом с переключателем, или переключатель её заменяет?
   > кнопка подставить лайваре остается
