# Сверка переноса документации MagicPro

Дата: 2026-09-15.

## Результат

Проверены все 41 Markdown-файл русской документации после переноса:
наличие, структура, ссылки, блоки кода и дословные повторы. Для семи исходных
файлов `main` выполнена отдельная содержательная сверка: ниже указано место
для каждого из 154 исходных заголовков вне блоков кода.

Сохранены уникальные правила, пояснения и сценарии. Эквивалентные примеры
объединены; ошибочные утверждения заменены описанием текущего кода, а не
продублированы в новых папках. Это сверка документации и точечное чтение
реализации по спорным местам; приложение, база и браузерные сценарии не запускались.

## Что было пропущено и восстановлено

| Материал | Где читать |
| --- | --- |
| Слоты компонентов, динамическая карточка, несколько экземпляров, атрибуты, шаблон, стеки, порядок загрузки JS, комментарии Blade, версии ассетов и переносы строк | [mainUse/layout.md](../docs/ru/mainUse/layout.md) |
| Общий публичный метод статьи-helper и способы вызова | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#статья-helper-общий-метод-для-сайта) |
| Источники путей изображений, баннер, логотипы, srcset/sizes, picture, фон, письмо, заглушка и стоимость первого запроса | [image/use.md](../docs/ru/image/use.md) |
| Простая семантическая разметка текстовых полей ленты | [feed/use.md → раздел](../docs/ru/feed/use.md#вёрстка-текста-записи) |
| Несколько позиционных параметров, UTM, специальные имена адресов и данные error404 | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#маршрут-статьи) |
| Изменение имени статьи, публичного маршрута и компонентов, генерация документации хелпера | [mainDev/use.md](../docs/ru/mainDev/use.md) |
| Инварианты и матрица проверок данных, маршрутов, рендеринга, дерева, статики и админки | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#инварианты), [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#матрица-проверки-изменения) |
| Установка, команды, nginx, обновление и восстановление установки | [mainDev/install.md](../docs/ru/mainDev/install.md) |
| Статическая публикация и её ограничения | [mainUse/static.md](../docs/ru/mainUse/static.md) |
| Общие ограничения и проверка входных значений хелперов | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |

## Как устранены дубли

- Полный материал старого `main` больше не хранится второй копией: семь файлов
  оставлены короткими указателями на соответствующие руководства.
- В `mainUse/use.md` остались статьи, маршруты и PHP; примеры шаблона, include
  и анонимного компонента находятся в `mainUse/layout.md`.
- Изображения и тексты лент описаны в своих модулях. Из вёрстки к ним ведут ссылки.
- Аргументы, ответы и порядок операций MCP со статьями собраны в `mainUse/mcp.md`.
  `mcp/use.md` содержит подключение, токен, документацию, ленты и обычные файлы.
- Директивы и получение администратора из PHP находятся в `users/use.md`;
  реализация guard и middleware — в `users/inside.md`. Ядро ссылается на них.
- `mainUse/dont.md` содержит навигационную таблицу ограничений и уникальные
  правила вызывающего кода, а не повторяет справочники модулей.
- В остальных модулях сохранено разделение по аудитории: использование API
  и внутренняя реализация — разные задачи. Совпадение имени метода, сигнатуры,
  короткого примера или ссылки само по себе не считалось дублем раздела.

Дословные повторяющиеся блоки длиной более 150 символов после нормализации
пробелов: **0** в `docs/ru/*.md` и вложенных папках. Это дополнительная
автоматическая проверка; смысловое объединение выполнено отдельно по темам.

## Исправленные расхождения с кодом

| Старое описание | Что отражено в итоговых документах | Проверенная реализация |
| --- | --- | --- |
| База принимает правку «всегда» | Сначала проверки модели; ошибочный PHP может сохраниться с warning без публикации | `Article::saving`, `API_ArticlesPostController::saveById` |
| Новая статья оставляет routeParams равным null | Событие модели подставляет ROUTE_PARAMS | `Article::routeParams`, `createNew` |
| Любое сохранение гарантирует файлы по текущему имени | Для createNew файлов ещё нет, warning оставляет старые; переименование отдельно влияет на адрес и класс | `saveById`, `MagicProBuilder` |
| Все ошибки маршрута становятся 404, catch-all принимает любой метод | HttpException сохраняет код; checkMethod ограничивает методы | `DynamicRouteHandler` |
| Наличие @csrf гарантирует обязательную защиту endpoint | Присланный токен проверяется, запрос без токена сейчас пропускается | `DynamicRouteHandler::checkToken` |
| Пример process без базового класса | Для страницы нужен MagicController; возвращённые ключи становятся переменными Blade | `MagicController`, заготовка контроллера |
| Регистр имени сам выбирает тип компонента | Blade сначала ищет класс, затем анонимную view; один регистр не является переключателем | установленный `ComponentTagCompiler::componentClass` |
| Bootstrap админки автоматически доступен публичной странице | Публичный шаблон должен подключать свои стили | `templateAdmin.blade.php`, генерация Blade статьи |
| get-doc недостаточно проверяет доступность страницы | Проверяются индекс, agent и наличие файла | `GetDocTool::offered` |
| Занятое имя удаляет старые файлы до валидации | Проверка модели предшествует публикации | `saveById` |
| Перемещение вниз ставит статью ниже требуемого соседа | Реализация корректирует позицию после освобождения старого места | `moveWithinParent` |
| Файловые инструменты сравнивают путь с неверным корнем | Используется PUBLIC_UPLOAD_DIR, realpath, запрет .. и проверка границы каталога | `API_FileManagerPostController::checkFileInPublicStorageDir` |
| Любое сохранение сразу обновляет все HTML-копии | Копия снимается при публикации своей статьи, зависимые страницы не пересобираются автоматически | `createMpro`, `deleteMpro`, `forgetStaticHtml` |
| URL и абсолютный путь различаются по начальному слэшу | Оба могут начинаться с /; вид пути определяется источником данных | `ImageJob`, `PUBLIC_UPLOAD_DIR` |

Это исправления описаний; исходники не менялись. Старые сообщения о дефектах
не оставлены как действующие ограничения после того, как исправление уже
найдено в коде.

## Замечания, которые не превращены в готовые возможности

В исходном `main/use.md` раздел быстрого старта содержал задачу:
«Нужно подготовить готовый сайт, который можно импортировать; оставить,
пока не делать». Готовый импортируемый сайт в этой работе не создавался.
Новый быстрый старт показывает создание одной страницы, а исходное пожелание
сохранено здесь как невыполненная задача.

В старом разделе внешних изображений фраза «Два пути» не имела продолжения.
В итоговом руководстве описан существующий контракт: ресайзер принимает
локальный файл и не загружает внешний URL.

## Индекс и доступность

`docs/ru/index.json` не изменён. Его исходная SHA-256:

`1a48d367925956638efa7b97c64222ce9283aeaeb8e090a955d8846e21094180`

Пока индекс указывает на `main`, эти страницы показывают указатели на новые
файлы. MCP не получает новую страницу только потому, что на неё есть Markdown-ссылка:
`get-doc` требует отдельного разрешения индекса. Подключение новых страниц
и флаги `agent` остаются отдельной согласованной задачей.

`mainUse/inside.md` и `mainDev/inside.md` остались пустыми (0 байт).
Сгенерированный `helpers/use.md` не редактировался. Шаблоны
`docs/mcp-install/` проверены на наличие при инвентаризации и не менялись;
это комплект запуска агента, а не второй экземпляр руководства по ядру.

## Изменённые файлы

- [users/use.md](../docs/ru/users/use.md)
- [mainDev/diagnostics.md](../docs/ru/mainDev/diagnostics.md)
- [mainDev/about.md](../docs/ru/mainDev/about.md)
- [mainDev/use.md](../docs/ru/mainDev/use.md)
- [mcp/inside.md](../docs/ru/mcp/inside.md)
- [mcp/use.md](../docs/ru/mcp/use.md)
- [feed/use.md](../docs/ru/feed/use.md)
- [mainUse/layout.md](../docs/ru/mainUse/layout.md)
- [mainUse/mcp.md](../docs/ru/mainUse/mcp.md)
- [mainUse/about.md](../docs/ru/mainUse/about.md)
- [mainUse/use.md](../docs/ru/mainUse/use.md)
- [main/dont.md](../docs/ru/main/dont.md)
- [main/layout.md](../docs/ru/main/layout.md)
- [main/static.md](../docs/ru/main/static.md)
- [main/install.md](../docs/ru/main/install.md)
- [main/inside.md](../docs/ru/main/inside.md)
- [main/editor.md](../docs/ru/main/editor.md)
- [main/use.md](../docs/ru/main/use.md)
- [image/use.md](../docs/ru/image/use.md)

Добавлены:

- [mainDev/install.md](../docs/ru/mainDev/install.md)
- [mainUse/dont.md](../docs/ru/mainUse/dont.md)
- [mainUse/static.md](../docs/ru/mainUse/static.md)

Все остальные существовавшие файлы `docs/` из исходного снимка сохранены
побайтово. Для изменённых руководств проверены конечные адреса ссылок
и якорей. Команды из примеров установки и проверок не выполнялись.

## Карта исходных разделов main

Номер строки относится к исходному файлу до этой сверки. Заголовки и их порядок
взяты из содержимого файлов, а не из предположения о структуре каталогов.
Новый порядок страниц в индексе здесь не задаётся.

| Исходный файл и строка | Исходный раздел | Итоговое место |
| --- | --- | --- |
| `main/dont.md:1` | Чего делать не надо | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:6` | Статьи и файлы | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:18` | Формы на страницах | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:24` | Запись целиком | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:30` | Хелперы | [mainUse/dont.md → раздел](../docs/ru/mainUse/dont.md#вызов-хелперов-из-кода-сайта) |
| `main/dont.md:41` | Картинки | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:49` | Почта и AWS | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/dont.md:57` | Крон | [mainUse/dont.md](../docs/ru/mainUse/dont.md) |
| `main/layout.md:1` | Правила вёрстки | [mainUse/layout.md](../docs/ru/mainUse/layout.md) |
| `main/layout.md:6` | Статьи | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#статьи) |
| `main/layout.md:8` | Общие правила | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#общие-правила) |
| `main/layout.md:17` | Пять ролей статьи | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#роли-статьи) |
| `main/layout.md:30` | Страница | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#страница) |
| `main/layout.md:47` | include | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#include) |
| `main/layout.md:69` | Компонент | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#компонент) |
| `main/layout.md:109` | Список: имя карточки вместо слота | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#список-имя-карточки-вместо-слота) |
| `main/layout.md:136` | Один компонент — много экземпляров | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#один-компонент--много-экземпляров) |
| `main/layout.md:155` | Шаблон | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#шаблон) |
| `main/layout.md:182` | Cтраница с контроллером — свои методы для всех статей | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#контроллер-статьи) |
| `main/layout.md:210` | helper — свои методы для всех статей | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#статья-helper-общий-метод-для-сайта) |
| `main/layout.md:246` | Стили и скрипты | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#стили-и-скрипты) |
| `main/layout.md:292` | Данные в javascript | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#данные-в-javascript) |
| `main/layout.md:301` | Картинки | [image/use.md → раздел](../docs/ru/image/use.md#подготовка-пути) |
| `main/layout.md:318` | Что делают хелперы | [image/use.md → раздел](../docs/ru/image/use.md#ответ) |
| `main/layout.md:342` | Ширина или высота | [image/use.md → раздел](../docs/ru/image/use.md#ресайз-по-высоте) |
| `main/layout.md:350` | Шаг первый: откуда взять путь | [image/use.md → раздел](../docs/ru/image/use.md#подготовка-пути) |
| `main/layout.md:374` | Внешний адрес | [image/use.md → раздел](../docs/ru/image/use.md#подготовка-пути) |
| `main/layout.md:379` | Примеры | [image/use.md → раздел](../docs/ru/image/use.md#примеры-разметки) |
| `main/layout.md:381` | Файл из файлового менеджера | [image/use.md → раздел](../docs/ru/image/use.md#баннер-из-файлового-менеджера) |
| `main/layout.md:396` | Поле записи ленты | [image/use.md → раздел](../docs/ru/image/use.md#вывод-в-blade) |
| `main/layout.md:419` | Логотипы одной высоты | [image/use.md → раздел](../docs/ru/image/use.md#логотипы-одной-высоты) |
| `main/layout.md:437` | Чёткость на телефоне: srcset | [image/use.md → раздел](../docs/ru/image/use.md#чёткость-на-телефоне-srcset) |
| `main/layout.md:471` | Форматы: avif, webp и jpg для старых браузеров | [image/use.md → раздел](../docs/ru/image/use.md#форматы-avif-webp-и-jpg-для-старых-браузеров) |
| `main/layout.md:501` | Фон блока | [image/use.md → раздел](../docs/ru/image/use.md#фон-блока) |
| `main/layout.md:514` | Письмо и соцсети: только jpg | [image/use.md → раздел](../docs/ru/image/use.md#письмо-и-превью-ссылки-явный-формат) |
| `main/layout.md:528` | Файл, который положил свой код | [image/use.md → раздел](../docs/ru/image/use.md#подготовка-пути) |
| `main/layout.md:534` | Ошибка и заглушка | [image/use.md → раздел](../docs/ru/image/use.md#ошибки) |
| `main/layout.md:557` | Грабли | [image/use.md → раздел](../docs/ru/image/use.md#как-работает-кеш) |
| `main/layout.md:592` | Где что искать | [image/use.md → раздел](../docs/ru/image/use.md#где-читать-дальше) |
| `main/layout.md:598` | $Env | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#переменные-страницы) |
| `main/layout.md:603` | Грабли | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#ошибки-вёрстки) |
| `main/layout.md:627` | Порядок работы | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#порядок-работы) |
| `main/layout.md:636` | Чего не делать | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#чего-не-делать) |
| `main/layout.md:647` | Ленты | [feed/use.md → раздел](../docs/ru/feed/use.md#вёрстка-текста-записи) |
| `main/static.md:1` | Статические HTML-страницы | [mainUse/static.md](../docs/ru/mainUse/static.md) |
| `main/install.md:1` | Установка | [mainDev/install.md](../docs/ru/mainDev/install.md) |
| `main/install.md:8` | Четыре команды | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#команды-установки) |
| `main/install.md:23` | nginx | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#nginx) |
| `main/install.md:69` | Дальше открыть /a_dmin | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#дальше-открыть-a_dmin) |
| `main/install.md:76` | Если на странице сообщение | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#если-на-странице-сообщение) |
| `main/install.md:96` | Если что-то сломалось потом | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#если-что-то-сломалось-потом) |
| `main/install.md:103` | Обновление | [mainDev/install.md → раздел](../docs/ru/mainDev/install.md#обновление) |
| `main/inside.md:1` | Ядро MagicPro: полное внутреннее устройство | [mainDev/use.md](../docs/ru/mainDev/use.md) |
| `main/inside.md:10` | Ответственность ядра | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#ответственность-ядра) |
| `main/inside.md:45` | Карта файлов | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#карта-файлов) |
| `main/inside.md:69` | Как пакет входит в Laravel | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#как-пакет-входит-в-laravel) |
| `main/inside.md:96` | Жизненный цикл провайдера | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#жизненный-цикл-провайдера) |
| `main/inside.md:98` | register | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#register) |
| `main/inside.md:110` | boot | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#boot) |
| `main/inside.md:133` | Настройки и вычисленные пути | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#настройки-и-вычисленные-пути) |
| `main/inside.md:165` | Структура данных статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#структура-данных-статьи) |
| `main/inside.md:200` | Проверки модели при saving | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#проверки-модели-при-saving) |
| `main/inside.md:217` | Формат routeParams | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#формат-routeparams) |
| `main/inside.md:246` | Рабочие файлы статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#рабочие-файлы-статьи) |
| `main/inside.md:263` | MagicProBuilder | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#magicprobuilder) |
| `main/inside.md:292` | Сохранение и операции над деревом | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#сохранение-и-операции-над-деревом) |
| `main/inside.md:323` | saveById | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#savebyid) |
| `main/inside.md:365` | createNew | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#createnew) |
| `main/inside.md:379` | copyRec | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#copyrec) |
| `main/inside.md:391` | move | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#move) |
| `main/inside.md:414` | deleteById | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#deletebyid) |
| `main/inside.md:427` | Чтение и служебные команды | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#чтение-и-служебные-команды) |
| `main/inside.md:444` | Архив статей | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#архив-статей) |
| `main/inside.md:478` | Регистрация Blade | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#регистрация-blade) |
| `main/inside.md:490` | Именованная view | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#именованная-view) |
| `main/inside.md:500` | Анонимный компонент | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#анонимный-компонент) |
| `main/inside.md:510` | Классовый компонент | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#классовый-компонент) |
| `main/inside.md:521` | Livewire | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#livewire) |
| `main/inside.md:536` | Маршрут статьи: режимы и примеры | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#маршрут-статьи) |
| `main/inside.md:562` | Параметры маршрута | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#маршрут-статьи) |
| `main/inside.md:574` | Страница без параметров | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#utm-параметры) |
| `main/inside.md:593` | Любые GET-параметры | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#список-с-фильтрами) |
| `main/inside.md:613` | Только выбранные GET-параметры | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#список-с-фильтрами) |
| `main/inside.md:626` | Позиционные параметры | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#несколько-позиционных-параметров) |
| `main/inside.md:659` | UTM параметры | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#utm-параметры) |
| `main/inside.md:671` | как сделать /robots.txt | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#файл-вроде-robotstxt) |
| `main/inside.md:675` | Запрос с телом | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#форма) |
| `main/inside.md:702` | Только для администратора | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#закрытая-страница) |
| `main/inside.md:713` | Что происходит при ошибке маршрута | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#страница-ошибки-маршрута) |
| `main/inside.md:722` | Выполнение публичного запроса | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#выполнение-публичного-запроса) |
| `main/inside.md:724` | Регистрация catch-all | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#регистрация-catch-all) |
| `main/inside.md:742` | Выбор статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#выбор-статьи) |
| `main/inside.md:761` | Разбор URL-параметров | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#разбор-url-параметров) |
| `main/inside.md:797` | postEnable | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#postenable) |
| `main/inside.md:806` | Окружение Blade | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#окружение-blade) |
| `main/inside.md:835` | Ошибка маршрутизатора | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#ошибка-маршрутизатора) |
| `main/inside.md:850` | MagicController | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#magiccontroller) |
| `main/inside.md:896` | Хелперы, aliases и Blade-директивы | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#хелперы-aliases-и-blade-директивы) |
| `main/inside.md:898` | MproHelper | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#mprohelper) |
| `main/inside.md:918` | Legacy helpers | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#legacy-helpers) |
| `main/inside.md:924` | Class aliases | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#class-aliases) |
| `main/inside.md:935` | Директивы | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#директивы) |
| `main/inside.md:943` | Админка и auth | [users/inside.md → раздел](../docs/ru/users/inside.md#guard-и-middleware) |
| `main/inside.md:974` | Группы маршрутов admin/web.php | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#группы-маршрутов-adminwebphp) |
| `main/inside.md:991` | Vue-редактор статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#vue-редактор-статьи) |
| `main/inside.md:1017` | Статическая публикация | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#статическая-публикация) |
| `main/inside.md:1021` | Обход в браузере | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#обход-в-браузере) |
| `main/inside.md:1036` | Получение и запись на сервере | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#получение-и-запись-на-сервере) |
| `main/inside.md:1057` | Установка и восстановление согласованности | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#установка-и-восстановление-согласованности) |
| `main/inside.md:1080` | Как менять ядро | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#найти-место-изменения) |
| `main/inside.md:1082` | Добавить поле статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#добавить-поле-статьи) |
| `main/inside.md:1095` | Добавить параметр маршрута | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#добавить-параметр-маршрута) |
| `main/inside.md:1110` | Изменить допустимое имя | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#изменить-допустимое-имя) |
| `main/inside.md:1126` | Изменить сохранение статьи | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#изменить-сохранение) |
| `main/inside.md:1142` | Добавить обычный публичный маршрут Laravel | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#добавить-обычный-публичный-маршрут-laravel) |
| `main/inside.md:1152` | Добавить анонимный компонент | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#компонент) |
| `main/inside.md:1158` | Изменить классовые или Livewire-компоненты | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#изменить-классовые-или-livewire-компоненты) |
| `main/inside.md:1170` | Добавить или изменить helper | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#развивать-отдельный-модуль) |
| `main/inside.md:1179` | Инварианты | [mainDev/use.md → раздел](../docs/ru/mainDev/use.md#инварианты) |
| `main/inside.md:1199` | Матрица проверки изменения | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#матрица-проверки-изменения) |
| `main/inside.md:1201` | Данные и генерация | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#данные-и-генерация) |
| `main/inside.md:1210` | Маршруты | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#маршруты) |
| `main/inside.md:1226` | Рендеринг | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#рендеринг) |
| `main/inside.md:1238` | Дерево | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#дерево) |
| `main/inside.md:1249` | Статика и админка | [mainDev/diagnostics.md → раздел](../docs/ru/mainDev/diagnostics.md#статика-и-админка) |
| `main/editor.md:1` | Редактор, дерево, архив версий | [mainUse/editor.md](../docs/ru/mainUse/editor.md) |
| `main/editor.md:3` | Дерево и редактор | [mainUse/editor.md → раздел](../docs/ru/mainUse/editor.md#дерево-и-редактор) |
| `main/editor.md:26` | Архив версий | [mainUse/editor.md → раздел](../docs/ru/mainUse/editor.md#архив-версий) |
| `main/use.md:1` | Ядро MagicPro: как пользоваться | [mainUse/use.md](../docs/ru/mainUse/use.md) |
| `main/use.md:7` | Основная идея | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#основная-идея) |
| `main/use.md:48` | Быстрый старт: первая страница | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#первая-страница) |
| `main/use.md:52` | Из чего состоит статья | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#из-чего-состоит-статья) |
| `main/use.md:72` | Три системные статьи: | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#три-системные-статьи) |
| `main/use.md:81` | Имя статьи и адрес | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#имя-статьи-и-адрес) |
| `main/use.md:92` | Маршрут статьи | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#маршрут-статьи) |
| `main/use.md:110` | Обычная страница | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#обычная-страница) |
| `main/use.md:115` | Список с фильтрами | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#список-с-фильтрами) |
| `main/use.md:131` | Карточка по адресу | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#карточка-по-адресу) |
| `main/use.md:144` | Форма | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#форма) |
| `main/use.md:167` | Закрытая страница | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#закрытая-страница) |
| `main/use.md:172` | Файл вроде `/robots.txt` | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#файл-вроде-robotstxt) |
| `main/use.md:176` | Blade статьи | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#blade-статьи) |
| `main/use.md:181` | Переменные страницы | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#переменные-страницы) |
| `main/use.md:203` | Layout | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#шаблон) |
| `main/use.md:233` | Include | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#include) |
| `main/use.md:251` | x-magic - анонимный Blade-компонент | [mainUse/layout.md → раздел](../docs/ru/mainUse/layout.md#компонент) |
| `main/use.md:272` | Хелперы | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#хелперы) |
| `main/use.md:290` | Авторизация админов в Blade | [users/use.md → раздел](../docs/ru/users/use.md#в-блейде) |
| `main/use.md:311` | Контроллер статьи | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#контроллер-статьи) |
| `main/use.md:352` | Статус, заголовки и redirect | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#статус-заголовки-и-redirect) |
| `main/use.md:370` | Вызов публичного метода другой статьи | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#статья-helper-общий-метод-для-сайта) |
| `main/use.md:393` | Эксперментальное | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#экспериментальные-компоненты) |
| `main/use.md:395` | Классовый компонент | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#классовый-компонент) |
| `main/use.md:426` | Livewire-компонент | [mainUse/use.md → раздел](../docs/ru/mainUse/use.md#livewire-компонент) |

