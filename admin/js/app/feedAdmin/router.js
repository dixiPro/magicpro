import { createRouter, createWebHashHistory } from 'vue-router';

import groupList from './groupList.vue';
import feedList from './feedList.vue';
import feedEdit from './feedEdit.vue';

/**
 * Адреса через хеш: страница админки одна, /a_dmin/feed, а экран определяется
 * тем, что после решётки. Серверные роуты трогать не нужно, и ссылку на
 * конкретную ленту можно кинуть коллеге:
 *
 *   /a_dmin/feed#/            список групп
 *   /a_dmin/feed#/group/2     ленты группы
 *   /a_dmin/feed#/feed/3      одна лента
 */
const routes = [
  { path: '/', name: 'groups', component: groupList },
  { path: '/group/:groupId', name: 'feeds', component: feedList, props: true },
  { path: '/feed/:feedId', name: 'feed', component: feedEdit, props: true },
  { path: '/:pathMatch(.*)*', redirect: { name: 'groups' } },
];

export default createRouter({
  history: createWebHashHistory(),
  routes: routes,
});
