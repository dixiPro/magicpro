**глюки**

- Завести статью error404 при инсталляции
- Глюки в форматтере
- отступы в форматтере
- Удалить index из главного маршрута при установке
- темы Ace Editor половина битые

**уточнить права**
/storage 766
файлы 666

**Проверка прав отдельная страница**

**admin/js/app/ArtEditor/component/EditArticle.vue**
в компонент Ace Editor
const ready = ref({
show: false
});

**admin/js/app/ArtEditor/component/AceEditor.vue**
Темы import через цикл
ready: { type: Boolean, default: false }, // не готово
