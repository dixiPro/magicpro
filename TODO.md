**глюки**

- установка проекта уточнить директории
  композер реквайр ларавел и пхп?
  изменение композер дзсон

- Завести статью error404 при инсталляции
- Глюки в форматтере
- отступы в форматтере
- Удалить index из главного маршрута при установке
- темы Ace Editor половина битые
- добавить в create_magicPro_articles_table генерацию 404 вьюхи

**уточнить права**
/storage 766
файлы 666

После установки выполните:
sudo chown -R soln:www-data /home/soln/work/web_projects/mpro.dixi.ru/dataMagicPro
sudo chown -R soln:www-data /home/soln/work/web_projects/mpro.dixi.ru/publicmagicPro

**admin/js/app/ArtEditor/component/AceEditor.vue**
Темы import через цикл
ready: { type: Boolean, default: false }, // не готово
