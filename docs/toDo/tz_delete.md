# Что удалить и почистить

Список того, что стало ненужным после переделки установки. Сам ничего не удаляю,
жду разрешения.

## Файлы

| Файл | Почему | Когда можно |
| --- | --- | --- |
| `admin/controller/AdminController copy.php` | копия перед переносом установки в `Installer` | после проверки `/a_dmin` |
| `admin/views/index copy.blade.php` | то же | после проверки `/a_dmin` |
| `composer copy.json` | старый бэкап | сразу |
| `admin/js/app/apiCall copy.js` | старый бэкап | сразу |
| `src/Installer/InstallCommand.php` | нигде не регистрируется, вызвать нельзя; в первой строке файла помечен как неиспользуемый | сразу или когда появится `magicpro:install` на базе `Installer` |
| `docs/TZ_lenta/` | ТЗ лент целиком перенесено в `docs/ru/feed/` | сразу |
| `docs/toDo/TZ_install4.md`, `docs/toDo/TZ_install5.md` | заменены `TZ_install6.md` | сразу |
| `docs/ru/common.md`, `docs/ru/inside.md`, `docs/ru/image.md` | перенесены в `docs/ru/main/` и `docs/ru/image/` | **не раньше**, чем `getDoc` научится вложенным папкам: статья `doc` читает `common.md` |

## Код

- `AdminController::testWrite()` — блок «Права на запись» убран из `/a_dmin/setup`,
  метод больше никто не зовёт. Права теперь проверяет `Installer` там, куда сам
  пишет.
- Маршрут `/a_dmin/api/testWrite` в `admin/web.php`, имя `magic.testWrite`.
- Импорт `Illuminate\Support\Facades\File` в `AdminController` — после удаления
  метода больше не нужен.
- Константа `MAGIC_FILE_ROLES` в `src/Config/MagicGlobals.php` — её читал только
  `testWrite()`.
- В `admin/views/setup.blade.php` мёртвый блок `session('regenerateArticles')`:
  цикл с пустым `div`, а саму сессию никто не ставит.

## Ключи словарей

Установка теперь говорит по-английски из `Installer`, старые подписи не
используются. Удалять из `lang/ru/messages.php` и `lang/en/messages.php`:

```
write_permissions
step_data_dir  step_vendor_public  step_upload_dir  step_storage_link
note_exists  note_created  note_copied
cannot_create_dir  cannot_create_link  hint_permissions
image_tools  tool_not_found
cron_title  cron_alive  cron_dead  cron_never  cron_fix
```

Остаются в деле: `diagnostics_title` — заголовок блока на `/a_dmin`, и
`regenerate_articles` — ссылка на `/a_dmin/setup`.

## Спорное

- `database/seeders/UserSeeder.php` — делает двести пользователей
  `App\Models\User` хост-приложения, к продукту отношения не имеет. Нужен для
  тестов или выкинуть?

## Проверить до удаления

- `/a_dmin` на здоровой установке: блок диагностики пуст.
- `/a_dmin` на сломанной: сообщения с командами на месте, и по-английски.
- `/a_dmin/setup`: осталась чистка кеша, перегенерация статей и phpinfo.
