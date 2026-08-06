import { createRouter, createWebHashHistory } from 'vue-router';

import groupList from './groupList.vue';
import feedList from './feedList.vue';
import feedEdit from './feedEdit.vue';
import feedStructure from './feedStructure.vue';
import feedListColumns from './feedListColumns.vue';
import feedBuilder from './feedBuilder.vue';
import dataGroups from './dataGroups.vue';
import dataFeeds from './dataFeeds.vue';
import dataItems from './dataItems.vue';
import dataItem from './dataItem.vue';

/**
 * Адреса через хеш: страница админки одна, /a_dmin/feed, а экран определяется
 * тем, что после решётки. Серверные роуты трогать не нужно, и ссылку на
 * конкретную ленту можно кинуть коллеге:
 *
 *   /a_dmin/feed#/            данные: список групп
 *   /a_dmin/feed#/data/group/2 данные: ленты группы
 *   /a_dmin/feed#/data/feed/3  данные: записи ленты
 *   /a_dmin/feed#/data/item/12 данные: одна запись
 *   /a_dmin/feed#/groups      структура: список групп
 *   /a_dmin/feed#/group/2     ленты группы
 *   /a_dmin/feed#/feed/3      схема ленты
 *   /a_dmin/feed#/feed/3/list колонки списка
 *   /a_dmin/feed#/feed/3/builder
 *
 * Ленту читает feedEdit, вкладки — его дети: лента одна, а экранов над ней
 * несколько.
 */
const routes = [
  { path: '/', name: 'data', component: dataGroups },
  { path: '/data/group/:groupId', name: 'dataFeeds', component: dataFeeds, props: true },
  { path: '/data/feed/:feedId', name: 'dataItems', component: dataItems, props: true },
  { path: '/data/item/:itemId', name: 'dataItem', component: dataItem, props: true },
  { path: '/groups', name: 'groups', component: groupList },
  { path: '/group/:groupId', name: 'feeds', component: feedList, props: true },
  {
    path: '/feed/:feedId',
    component: feedEdit,
    props: true,
    children: [
      { path: '', name: 'feed', component: feedStructure },
      { path: 'list', name: 'feedList', component: feedListColumns },
      { path: 'builder', name: 'feedBuilder', component: feedBuilder },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'data' } },
];

export default createRouter({
  history: createWebHashHistory(),
  routes: routes,
});
