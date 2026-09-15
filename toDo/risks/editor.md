# Редактор статей: незакрытое

Фронт редактора: дерево, копирование, горячие клавиши, заготовки.

Багов: 1. Список всех подсистем — `toDo/risks.md`.

---

## 1. Livewire-заготовка жёстко привязана к lvcomponent

**Где:**

- `admin/controller/default/defaultControllerLivewire.php`;
- `docs/ru/main/use.md`, раздел LiveWire.

В заготовке class placeholder заменяется на имя статьи, но `render()` всегда
возвращает:

```php
return view('magic::lvcomponent', ...);
```

Для статьи с любым другим именем её собственный Blade не используется. Старый
документ показывает `magic::Magic_Pro_Name_Controller`, что после общей замены
дало бы правильное имя, но фактический шаблон устроен иначе.

_Было: главный 13._
