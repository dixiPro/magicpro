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

Хелперы статей находятся `packages\dixipro\magicpro\src\Helpers\MproHelper.php`

Вызываются `MproHelper::ИмяХелпера('Параметры')`

### getChildrenById

возвращает список детей статьи по ее Id у которых menuOn = true
Можно построить меню сайта

### getPathToRootById

Возвращает путь до корня. Можно построить хлебные крошки.

### dump

Выводит дамп переменной в виде JSON в теге <xmp>
