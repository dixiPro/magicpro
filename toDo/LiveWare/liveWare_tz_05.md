# ТЗ v5: Livewire в MagicPro

Основа — `liveWare_tz_04.md` с ответами владельца. Открытых вопросов нет.

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
6. **Галка «Livewire-компонент» в настройках маршрута статьи.**
   - Поставил — `isRoute` выключен, `useController` включён, и в настройках
     маршрута кроме этой галки больше ничего не видно.
   - Снял — настройки маршрута снова видны. Значения при этом не трогаются.
   - Ничего не вставляет в текст и ничего не проверяет при сохранении: админ,
     который хочет навредить, найдёт как.
7. **Флаг хранится в `routeParams`**: ключ `livewire` (`true` / `false`),
   по умолчанию `false`.
8. **Кнопка «Livewire контроллер»** в автодополнении редактора остаётся — она
   подставляет заготовку.
9. **Кнопка и команда API** переименовываются: «LiveWare» → «Livewire».
10. **МСП:** ключ `livewire` добавляется в `routeParams` инструмента
    `save-article`, агент делает компоненты сам.
11. **Дока:** ссылка на документацию Livewire.
12. **Тестовые статьи** 288, 289, 355–358 — решаем после того, как система
    готова.

## Как это работает

Статья `counter`: галка «Livewire-компонент» стоит, кнопкой «Livewire
контроллер» подставлена заготовка. Контроллер:

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

| Где | Что |
| --- | --- |
| `src/Livewire/MagicProLivewire.php` | базовый класс с `render()` |
| `src/Livewire/LivewireComponentRegistry.php` | перенос из `src/`, только ветка `magic::` |
| `MagicServiceProvider` | реестр из нового места |
| `admin/controller/default/defaultControllerLivewire.php` | заготовка: наследник `MagicProLivewire`, одно свойство, один метод, без `render()` |
| `Article::ROUTE_PARAMS` | ключ `livewire`, умолчание `false` |
| Редактор статьи, настройки маршрута | галка «Livewire-компонент»: ставит `isRoute = false`, `useController = true`, прячет остальные настройки маршрута |
| `translate.js` | подпись галки |
| МСП, `SaveArticleTool` | `livewire` в схеме `routeParams` с описанием |

### Удалить / переименовать

| Где | Что |
| --- | --- |
| `src/LivewireComponentRegistry.php` | уходит, класс переехал в `src/Livewire/` |
| `LivewireComponentRegistry` | ветка `magic-pro-controllers.имя` |
| `defaultControllerLivewire.php` | `extends Component`, свой `render()` с `view('magic::lvcomponent')` |
| `MagicProBuilder.php` | `readDefaultLiveWareController` → `readDefaultLivewireController` |
| `API_ArticlesPostController` | команда `getDefaultLiveWareController` → `getDefaultLivewireController` |
| `admin/js/app/ArtEditor/store.js`, `Autocomplete.vue` | `getLiveWareController` → `getLivewireController` |
| `translate.js` | ключ `autocomplete_liveWare_controller` → `autocomplete_livewire_controller` |
| `toDo/risks/editor.md`, п. 1 | риск «заготовка привязана к `lvcomponent`» снимается |

### Дока

- `docs/ru/mainUse/use.md`, раздел «Livewire-компонент» — переписать: галка,
  заготовка, базовый класс, вызов тегом, свойства в блейде, когда нужен свой
  `render()`. Флаг `livewire` — в таблицу `routeParams`. Ссылка на
  документацию Livewire: <https://livewire.laravel.com/docs>.
- `docs/ru/mainUse/mcp.md` — `livewire` в `routeParams` агента.
- `docs/ru/mainDev/use.md` — карта файлов, реестр, команда API.
- `docs/ru/mainDev/diagnostics.md` — проверка Livewire под новое устройство.

## Проверка

1. `php -l` изменённых PHP-файлов, `npm run build` админки.
2. В редакторе: галка ставится — настройки маршрута прячутся, `isRoute` и
   `useController` выставлены; снимается — настройки видны снова.
3. Статья-компонент по заготовке сохраняется без `warning`, страница с тегом
   `<livewire:magic::имя />` открывается, нажатие кнопки меняет значение.
4. Переименование статьи-компонента: тег с новым именем работает без правки
   контроллера.
5. МСП: `save-article` с `routeParams.livewire = true` сохраняет флаг.
