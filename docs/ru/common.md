# Структура проекта

## articles таблица

- id
- parentId айди родителя
- npp номер по порядку у родителя
- name имя это раут name=about будет раут /about
- title заголовок, часто использвется в html `<title>`
- controller текст контроллера статьи
- body вьюъа
- templateName не испоьлзуется
- directory признак, что статья содержит детей
- menuOn - участвует в меню или нет
- isRoute - признак что статья присутстует в рауте
- routeParams - параметры маршрута
- created_at
- updated_at

Корневой элемент имеет id 0 и не удаляем. Корневой элемент ничем не отличается от другого.

## Контроллеры и блейды

- Каждая статья имеет блейд.
- Каждая статья может иметь контроллер.

Если статья имеет контроллер, то управление передается контроллеру, который уже потом рендерит блейд.

Если статья не имеет контроллера, то сразу рендериться вью.

## Откуда беруться вью и контроллеры

Когда в админке сохранятете статью, система автоматически

- записываыет блейд в `storage/dataMagicPro/view/имя_статьи.blade.php`
- когда есть контроллер то он записывается в `storage/dataMagicPro/controller/имя_статьи.php`

## раутинг

Раутинг определяется двумя полями

- isRoute - признак что статья присутстует в рауте
- name имя это раут name=about будет раут /about
- routeParams - параметры маршрута

Возможно GET и POST обращение

### Параметры маршрута

В поле routeParams храниться JSON

```JSON
"routeParams": {
    "useController": false,
    "adminOnly": false,
    "utmParamsEnable": true,
    "getEnable": false,
    "postEnable": false, //
    "bindKeys": false,
    "keysArr": []
},
```

#### useController

Использовать контроллер или нет.
Если true управление передается контроллеру статьи, который потом рендерить вью.
Если false рендериться view статьи

#### adminOnly

Cтатью может видетьтолько администратор

#### UTM разрешено

`utmParamsEnable:true`

Могут использоваться utm параметры. Список допустимых устанавливается в админке в Setup

    Валидныйф пример `/test?utm_source=source`

    Не валидный `/testfirst/1/?utm_source=source`

`utmParamsEnable:false`

Наличие Utm параметров вызовет 404 ошибку

По умолчанию `false`

#### Разрешены GET параметры

`getEnable:true`

    Валидный `/test/first/1/second/2?utm_source=source`

    Валидный `/test?first=1?second=2&utm_source=source`

Допустимы любые Get параметры.

По умолчанию `false`

#### Только заранее определенные Get параметры

Хранятся в `"keysArr": []`

Utm обрабатывается отдельно и не входит в keysArr

Параметры можно указывать через / или через param=value

Пусть `"keysArr" : ["first"]`

    Валидный `/test/first/1?utm_source=source`

    Валидный `/test?first=1&utm_source=source`

    Не валидный `/test/first/1/second/2?utm_source=source`

Когда `"keysArr":[]` разрешены любые Get параметры

#### Фиксированные параметры

`bindKeys": true`
`"keysArr" : ["first"]`

Значение параметров определяется положением в url

    Валидный `/test/1?utm_source=source`

    Не валидный `/test/utm_source=source`

    Не Валидный `/test/first/1?utm_source=source`

### Post запрос

`postEnable: true`
Все Get запросы будут проигнорированы. Обращение через Get ошибку не вызовет, но и параметры переданы не будут.

Post нужно обрабатывать самостоятельно

## View / блейды. Особые конструкции

Поддерживаются все конструкции Ларавела. Некоторые имеют особенности.

### $Env

Каждый view получает переменную $Env в которой

```JSON
{
    "name": "docs", //  имя текущей статьи
    "title": "Документация", //  title текущей статьи
    "artId": 139, //  id текущей статьи
    "parentId": 1, //  id родителя
    "view": "magic::docs" // имя вьюхи
}
```

### Общие положеня

Везде где вы используете имя vie вы можете использовать 'magic::имя статьи'
Будет подключен блейд из статьи

### Примеры

### include

`@include('magic::articleName', [data])`

Пример `@include('magic::test', ['first'=>1, 'second'=>2])`

Заинклудид статью test и передаст туда параметры. Все как в Ларавеле. Когда хотите включить статью нужно указать префикс `magic::`

### @extends

`@extends('magic::main26', [])`
Использует в качестве шаблона статью `main26`

### Анонимный компонент

`<x-magic::anonymous_component name1="value1" name2="value2" />`

`anonymous_component` имя статьи. Рендерить блейд. Контроллер не используется

**anonymous_component - только прописными**

### ClassBased компонент

`<x-magic::ClassBasedAcrticle name1="value1" name2="value2"/>`

`ClassBasedAcrticle` имя статьи. Вызываыет контроллер статьи.

**ClassBasedAcrticle - должен быть ЗаглавНыми**

## Контроллеры

Контроллер и блейд лежат в одной статье. И контпроллер рендерит тот блейд, который находится с ним в одной статье.

В админке есть пункт "лампочка" который вставляет стандартные тексты разных контроллеров.

### Простой контроллер

Когда статья используется в рауте. Контроллер долженр быть такой

```php
<?php

namespace MagicProControllers;
use Illuminate\Http\Request;
use MagicProSrc\MagicController;
class Magic_Pro_Name_Controller extends MagicController
{
    protected function process(array $params): array
    {
        $request = $params['request'];
        $getParams = $params['getParams'] ?? [];
        $postParams = $params['postParams'] ?? [];

// здесь ваш код

        return ["Get" => $getParams]; // это будет передано блейду.
    }
}
```

`$params` пока имеет три поля `request` `getParams` `postParams`

### Контроллер ClassBased компонента, опытная эксплуатация, может быть изменен

В текущих проектах он нигде не используется, но тестировался. Пока контроллер такой. Возможно я его переделаю.

```php
<?php

namespace MagicProControllers;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
class Magic_Pro_Name_Controller extends Component
{
    public string $name; // это имя переменной передающиеся в компонент

    public function __construct(string $name = '')
    {
        $this->name = $name; // должно тут быть
    }

    public function render(): View
    {
        $time = now()->format('H:i:s');

        // это уезжает в  блейд
        $nameWithTime = $this->name . ' ' . $time;

        return view('magic::' . class_basename(static::class), [
         'nameChat' => $nameWithTime,
        ]);
    }
}
```

### Контроллер LiveWare опытная эксплуатация, может быть изменен

В текущих проектах он нигде не используется, но тестировался. Пока контроллер такой. Возможно я его переделаю.

```php
<?php
namespace MagicProControllers;
use Livewire\Component;

class Magic_Pro_Name_Controller extends Component
{
    public string $inputText = "Livewire";
    public string $title = ""; // 👈 обязательно объявить, если передается в компонент

    public function render()
    {
        return view("magic::Magic_Pro_Name_Controller", [
            "text" => $this->inputText,
            "title" => $this->title,
        ]);
    }
}
```

## Хелперы сайта

Все вспомогательные методы собраны в одном классе `src/Helpers/MproHelper.php` и
вызываются статически.

Класс лежит в глобальном пространстве имён и подключается через `require_once` в
`MagicServiceProvider`, поэтому в блейдах и контроллерах доступен сразу, без `use`.

### Дерево статей

Из этих методов строятся меню, хлебные крошки и переходы по иерархии.

| Метод | Что делает |
| --- | --- |
| `getArtById(int $id)` | Статья целиком по id, все поля. Если не найдена — пустой массив. |
| `getArtByName(string $name)` | То же самое, но по имени статьи. |
| `getParent(int $id)` | Родительская статья целиком. Для корня и для несуществующего id возвращает пустой массив, а не ошибку. |
| `getChildrenById(int $artId)` | Дети статьи по её id, **только с `menuOn = true`**, по порядку `npp`. Поля: id, title, name, menuOn, updated_at, npp. |
| `getChildrenByName(string $name)` | То же самое, но родитель ищется по имени. Удобно для меню: имя стабильнее id. |
| `getPathToRootById(int $id)` | Путь до корня массивом имён, от корня к самой статье. Ограничен 100 шагами от зацикливания. |

Разница между двумя семействами: `getArt*` и `getParent` отдают статью целиком, со всеми
полями. `getChildren*` отдают короткие записи для меню и отсеивают всё, у чего
`menuOn = false`.

```blade
<ul>
@foreach( MproHelper::getChildrenByName('topMenu') as $child )
    <li><a href="/{{ $child['name'] }}">{{ $child['title'] }}</a></li>
@endforeach
</ul>

{{-- ссылка на родительскую статью --}}
@php( $parent = MproHelper::getParent($Env['artId']) )

@if( $parent )
    <a href="/{{ $parent['name'] }}">Вверх: {{ $parent['title'] }}</a>
@endif
```

### Отладка

| Метод | Что делает |
| --- | --- |
| `dump($var, bool $showXmp = true)` | Печатает переменную как JSON с отступами. При `$showXmp = true` оборачивает в `<xmp>`, чтобы разметка внутри не рендерилась. Ничего не возвращает, а выводит сразу. |

```blade
{{ MproHelper::dump($Env) }}
```

### Почта и логи

| Метод | Что делает |
| --- | --- |
| `sendMail(array $params)` | Отправляет письмо сразу, через `API_Mail`. Ключи: `email`, `subj`, `html`. Возвращает `status / errorMsg / data`, исключений не бросает. Каждая попытка, успешная и нет, пишется в лог `mail`. |
| `addLog(string $logName, string\|array $data)` | Пишет в `storage/logs/{$logName}.log` с ротацией за 14 дней. Массив раскладывается построчно в `ключ: значение`, вложенные — в JSON. |

```php
$res = MproHelper::sendMail([
    'email' => 'user@example.com',
    'subj'  => 'Тема письма',
    'html'  => '<h1>Привет</h1>',
]);

if (! $res['status']) {
    MproHelper::addLog('myLog', ['error' => $res['errorMsg']]);
}
```

### Внешние сервисы

| Метод | Что делает |
| --- | --- |
| `telegramSend($message, $chat_id, $botToken, $mode = 'HTML')` | Шлёт сообщение ботом в Telegram. Возвращает ответ API массивом, попытку пишет в лог `telegram`. |
| `getRecaptureKey()` | Публичный site key Google reCAPTCHA, из `RECAPTCHA_SITE_KEY` в `.env`. Нужен, чтобы блейды не читали `env()` напрямую и ключ лежал в одном месте. Этот ключ виден в исходнике страницы, так и задумано — секретный ключ сюда не попадает, он используется только при проверке токена. |
| `verifyRecapture(string $response)` | Проверяет токен Google reCAPTCHA. `true` только при успешной проверке; любая ошибка сети даёт `false`. |

### Шифрование

AES-256-CBC со случайным IV. Удобно для токенов в ссылках подтверждения.

| Метод | Что делает |
| --- | --- |
| `crypt(array $data, string $key)` | Шифрует массив в строку base64. IV кладётся в начало, так что результат самодостаточен. |
| `decrypt(string $data, string $key)` | Обратная операция. При неверном ключе или битых данных возвращает пустой массив, а не ошибку. |

```php
$token = MproHelper::crypt(['id' => 42, 'email' => $email], $key);
$data  = MproHelper::decrypt($token, $key);   // ['id' => 42, 'email' => ...]
```

### Текст и файлы

| Метод | Что делает |
| --- | --- |
| `trimAndCutText(string $text, int $limit = 0)` | Чистит текст для мета-тегов: снимает HTML, раскодирует сущности, убирает эмодзи и лишние пробелы. При `$limit > 0` обрезает по границе слова, а не посредине. |
| `imageType(string $text)` | MIME-тип по расширению файла: jpg, jpeg, png, webp, gif. Для остальных — пустая строка. Нужен для `og:image:type`. |
