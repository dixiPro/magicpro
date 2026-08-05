### 6.1. Пример описания

```json
{
  "version": 1,
  "fields": [
    {
      "column": "string_1", // тип строка
      "code": "title", // название, как будем обращаться в CRUD
      "label": "Заголовок", // что будет в админке
      "required": true, // обязательно поле или нет
      "unic" : true, // уникальность, если не указано  "unic" false
      "default": null, // как инициализировать
      "validation": { "type": "string", "min":4, "max":255 } // тип валидации строковых параметров отдельным пунктом
    },
    {
      "column": "bigint_1", // тип число
      "code": "price", // как будем обращаться в CRUD
      "label": "цена", //
      "required": true, // обязательно поле или нет
      "default": 100, // как инициализировать
      "validation": { "type": "integer", "min":100, "max":1000 } //
    },
    {
      "column": "date_1", // тип дата
      "code": "expiration", // например, срок годности
      "label": "срок годности", // что будет в админке
      "required": true, // обязательно поле или нет

      // "default": 'now', текущая дата
      // "validation": никак, главное что бы был валидный формат дата - время
    },
    {
      "column": "bool_1", // тип бул
      "code": "hasDiscount", //
      "label": "есь скидка", // что будет в админке
      "required": true, // обязательно поле или нет
      "default": false
    },
    {
      "column": "link_1",
      "code": "category",
      "label": "Категория",
      "editor": "select", // или textSearch
      "relation": {
        "feed_id": 22, // ади ленты
        "display_code": "string_1" // имя поля не code!
      }
    },
    {
      "column": "data",
      "code": "content",
      "data" :[
        {
          "type" : "string",
          "code": "subTitle", //
          "label" : "Подзаголовок",
          "required": true, // обязательно поле или нет
          "default": null,
          "validation": { "type": "string", "min":4, "max":255 } //
        },
        {
          "type" : "string",
          "code": "email2", //
          "label" : "запасной емайл",
          "required": true, // обязательно поле или нет
          "default": null,
          "validation": { "type": "email" } //
        },
        {
          "type" : "bigint",
          "code": "discont", //
          "label" : "скидка",
          "required": false, // обязательно поле или нет
          "default": null,
         "validation": { "type": "integer", "min":100, "max":1000 } //
        },
        {
          "type" : "date",
          "code": "bornDate", // например, срок годности
          "label": "дата производства", // что будет в админке
          "required": true, // обязательно поле или нет
        }
        // link пока не делаем, но ведь можно!
        {
          "type" : "text",
          "code": "description", // например, срок годности
          "label": "описание", // что будет в админке
          "required": true, // обязательно поле или нет
          "default": null,
          "validation": { "type": "string", "min":40, "max":2555 }, //
          "editor": "html", // "md", "plain" html сохраняет сущности, md и plane все хтмл сущности удаляются.
        },
        {
          "type" : "image",
          "code": "userPhoto", // например, срок годности
          "label": "Фото", // что будет в админке
          "required": false, // обязательно поле или нет
        },


      ]
  ]
}
```

### Пример данных

Одна запись этой ленты. Так она лежит в `feed_items`:

```json
{
  "id": 512,
  "feed_id": 7,
  "string_1": "Сыр Пармезан 200 г", // title
  "bigint_1": 990, // price
  "date_1": "2026-12-31 00:00:00", // expiration
  "bool_1": true, // hasDiscount
  "link_1": 118, // category, id записи из ленты 22
  "data": {
    "subTitle": "Выдержка 24 месяца",
    "email2": "sales@example.com",
    "discont": 500,
    "bornDate": "2025-12-01 00:00:00",
    "description": "<p>Твёрдый сыр из коровьего молока.</p>",
    "userPhoto": {
      "mime": "image/webp",
      "url": "/storage/2/photo2.webp",
      "size": 182340,
      "alt": "Иван Петрович",
      "x": 500,
      "y": 700
    }
  },
  "position": 3,
  "visible": true,
  "created_at": "2026-08-02 11:20:41",
  "updated_at": "2026-08-02 11:20:41"
}
```
