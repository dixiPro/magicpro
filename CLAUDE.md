# CLAUDE.md — MagicPro package

**MagicPro** is a CMS that combines the speed of a CMS with the flexibility of Laravel 13.
Controllers, Blade views, routes, pages, menus and settings are managed through a single
database structure (Articles) and an admin panel instead of scattered files.

This file only says where to read. Nothing is imported here: the documentation changes on
its own, this file stays.

## Read before touching the code

`docs/ru/claudeRules/rules.md` — the working rules: what to do without asking, what never to
touch, how to check the work, how to answer. Open it first.

## What you touch → what to read

| Работа | Дока |
| --- | --- |
| статьи, маршруты, генерация вьюх и контроллеров, настройки, админка | `docs/ru/main/` |
| установка и обновление | `docs/ru/main/install.md` |
| ленты: вывод на сайте, схема, слоты, API | `docs/ru/feed/` |
| картинки: ресайз на лету, кеш, форматы, кроппер | `docs/ru/image/` |
| блейд-компоненты сайта: картинка, пагинатор | `docs/ru/components/` |
| почта: отправка, очередь, SMTP и SES | `docs/ru/mail/` |
| `MproHelper`: дерево статей, логи, шифрование, тексты | `docs/ru/helpers/` |
| MCP-сервер и его инструменты | `docs/ru/mcp/` |

В каждой папке три файла:

- `about.md` — зачем это и что умеет;
- `use.md` — как пользоваться снаружи, из блейдов, контроллеров и API;
- `inside.md` — как устроено внутри, со структурой данных.

Читать `use.md`, когда пишешь по модулю; `inside.md` — когда меняешь сам модуль.

## Задачи и находки

`docs/toDo/` — что предстоит сделать. ТЗ пишется туда же, документация — это то, что уже
сделано, а не то, что задумано.
