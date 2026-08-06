import { defineStore } from 'pinia';
import { ref, reactive, computed } from 'vue';
import { apiFeed } from './api.js';

/**
 * Схема открытой ленты.
 *
 * Здесь живут обе схемы: считанная с сервера и та, что сейчас в таблицах.
 * По их расхождению видно, есть ли что сохранять, и по считанной же понятно,
 * лежит поле в базе или его только что добавили на экране.
 *
 * Компоненты типов правят строки напрямую, поэтому пропсов между ними нет.
 */
export const useFeedStore = defineStore('feed', () => {
  // сколько слотов каждого типа есть в таблице, как в миграции
  const SLOTS = { date: 3, link: 3, bool: 3, string: 5, bigint: 3 };

  const PREFIX = {
    date: '__date_',
    link: '__link_',
    bool: '__bool_',
    string: '__string_',
    bigint: '__bigint_',
  };

  // заготовка правил у новой строки
  const VALIDATION = {
    date: 'required|date',
    link: 'nullable|integer',
    bool: 'required|boolean',
    string: 'required|string|max:255',
    bigint: 'required|integer',
  };

  const feedId = ref(0);
  const code = ref('');
  const title = ref('');
  const groupId = ref(null);
  const itemsCount = ref(0); // есть записи — code полей уже не переименовать

  // схема, как её отдал сервер: точка отсчёта
  const schema = ref({ version: 1, fields: [] });

  const rows = reactive({ date: [], link: [], bool: [], string: [], bigint: [] });

  // контейнер __data: оператор правит только массив data, обёртку ставим сами
  const jsonText = ref('');
  const containerCode = ref('content');

  // порядок колонок списка: имена полей, у слота колонка, у поля __data его code
  const order = ref([]);

  // типы полей __data, порядок как в ТЗ
  const DATA_TYPES = ['string', 'text', 'integer', 'decimal', 'boolean', 'datetime', 'json', 'code', 'image'];

  // ленты этой же группы: связывать разрешено только внутри неё
  const groupFeeds = ref([]);

  function groupKeyOf(column) {
    if (typeof column !== 'string') return null;

    return Object.keys(PREFIX).find((key) => column.startsWith(PREFIX[key])) ?? null;
  }

  async function load(id) {
    feedId.value = Number(id);

    try {
      const feed = await apiFeed({ command: 'feedGet', id: feedId.value });

      code.value = feed.code;
      title.value = feed.title;
      groupId.value = feed.group_id;
      schema.value = feed.schema ?? { version: 1, fields: [] };

      for (const key of Object.keys(rows)) {
        rows[key] = [];
      }

      for (const field of schema.value.fields ?? []) {
        const key = groupKeyOf(field.column);

        if (!key) continue;

        rows[key].push({
          column: field.column,
          code: field.code ?? '',
          label: field.label ?? '',
          // у дат умолчания нет: записанная в схему дата через год протухнет
          default: key === 'date' ? '' : field.default ?? (key === 'bool' ? false : ''),
          unique: field.unique === true,
          // ключа нет — поле показывается: лишняя колонка заметна, пропавшая нет
          showOnList: field.showOnList !== false,
          // маска даты в списке, буквами PHP. Пусто — как лежит в базе
          listFormat: field.listFormat ?? '',
          validation: field.validation ?? '',
          relationFeedId: field.relation?.feed_id ?? null,
          relationColumn: field.relation?.display_code ?? '',
          relationTitle: '',
        });
      }

      const container = (schema.value.fields ?? []).find((field) => field.column === '__data');

      containerCode.value = container?.code ?? 'content';
      jsonText.value = JSON.stringify(container?.data ?? [], null, 2);

      order.value = Array.isArray(schema.value.order) ? schema.value.order.slice() : [];

      groupFeeds.value = await apiFeed({ command: 'feedsList', groupId: feed.group_id });

      await describeLinks();

      const list = await apiFeed({ command: 'itemsList', feedId: feedId.value, perPage: 1 });

      itemsCount.value = list.total;
    } catch (error) {}
  }

  // подписи связей: название целевой ленты и code её поля
  async function describeLinks() {
    for (const row of rows.link) {
      if (!row.relationFeedId) continue;

      const feed = groupFeeds.value.find((item) => item.id === row.relationFeedId);

      let fieldCode = row.relationColumn;

      try {
        const target = await apiFeed({ command: 'feedGet', id: row.relationFeedId });

        fieldCode =
          (target.schema?.fields ?? []).find((field) => field.column === row.relationColumn)?.code ??
          row.relationColumn;
      } catch (error) {}

      row.relationTitle = (feed?.title ?? row.relationFeedId) + ' · ' + fieldCode;
    }
  }

  // поля выбранной ленты: оператор выбирает по code, в схему уедет колонка
  async function fieldsOfFeed(id) {
    if (!id) return [];

    try {
      const target = await apiFeed({ command: 'feedGet', id: Number(id) });

      return (target.schema?.fields ?? [])
        .filter((field) => field.column && field.column !== '__data')
        .map((field) => ({ column: field.column, code: field.code, label: field.label }));
    } catch (error) {
      return [];
    }
  }

  // новое поле в первый свободный слот своей группы
  function addRow(key) {
    const taken = rows[key].map((row) => row.column);

    for (let i = 1; i <= SLOTS[key]; i++) {
      const column = PREFIX[key] + i;

      if (!taken.includes(column)) {
        rows[key].push({
          column: column,
          code: '',
          label: '',
          default: key === 'bool' ? false : '',
          unique: false,
          showOnList: true,
          listFormat: '',
          validation: VALIDATION[key],
          relationFeedId: null,
          relationColumn: '',
          relationTitle: '',
        });

        return;
      }
    }

    document.showToast('slots', 'error');
  }

  /**
   * Убрать поле.
   *
   * Строки, которой нет в считанной схеме, нет и на сервере — достаточно убрать
   * её с экрана. Существующее поле удаляется сразу, и уходит при этом не то,
   * что в таблицах, а считанная схема минус это поле: иначе удаление протащило
   * бы с собой недоделанные правки соседних строк.
   */
  async function removeRow(key, row) {
    const saved = (schema.value.fields ?? []).some((field) => field.column === row.column);

    if (!saved) {
      rows[key] = rows[key].filter((item) => item.column !== row.column);

      return true;
    }

    await apiFeed({
      command: 'schemaSave',
      feedId: feedId.value,
      schema: {
        version: schema.value.version ?? 1,
        fields: (schema.value.fields ?? []).filter((field) => field.column !== row.column),
      },
    });

    await load(feedId.value);

    return true;
  }

  // строка таблицы -> поле схемы
  function toField(key, row) {
    const field = {
      column: row.column,
      code: row.code.trim(),
      label: row.label.trim(),
    };

    if (key === 'link') {
      // в схему уходит физическая колонка целевой ленты, не её code
      field.relation = {
        feed_id: row.relationFeedId,
        display_code: row.relationColumn,
      };
    } else if (key !== 'date') {
      // дата умолчания не имеет: пустое поле формы заполняется текущей
      const value = row.default;

      if (value !== '' && value !== null) {
        field.default = key === 'bigint' ? Number(value) : value;
      }
    }

    // уникальность есть только у строк
    if (key === 'string' && row.unique) {
      field.unique = true;
    }

    // в схему уходит только отказ: показ — умолчание, писать его незачем
    if (!row.showOnList) {
      field.showOnList = false;
    }

    // маска есть только у дат, пустую в схему не пишем
    if (key === 'date' && row.listFormat.trim() !== '') {
      field.listFormat = row.listFormat.trim();
    }

    if (row.validation.trim() !== '') {
      field.validation = row.validation.trim();
    }

    return field;
  }

  /**
   * Заготовка поля __data: всё, что у этого типа бывает, с рабочими значениями.
   *
   * label ставится равным code, а не пустым: схема с пустым label не сохранится,
   * и кнопка выдавала бы заведомо битую заготовку.
   *
   * showOnList в заготовке false, хотя умолчание схемы — true: поле только что
   * появилось, в списке ему делать нечего, а ключ на виду и включается правкой
   * одного слова.
   */
  function dataFieldTemplate(type, code) {
    const field = { type: type, code: code, label: code };

    if (type === 'boolean') {
      field.default = false;
    } else if (type !== 'image') {
      field.default = null;
    }

    field.showOnList = false;

    const validation = {
      string: 'required|string|max:255',
      text: 'required|string',
      integer: 'nullable|integer',
      decimal: 'nullable|numeric',
      boolean: 'boolean',
      datetime: 'required|date',
      json: 'nullable',
      code: 'nullable|string',
    };

    // у image validation нет: поле необязательное
    if (validation[type]) {
      field.validation = validation[type];
    }

    if (type === 'text') {
      field.editor = 'html';
    }

    // маска списка буквами PHP: в списке нужна дата, а не время до секунды
    if (type === 'datetime') {
      field.listFormat = 'y-m-d';
    }

    // ограничения кропа: ширина в пикселях, пропорции строкой «ширина/высота»
    if (type === 'image') {
      field.minWidth = 600;
      field.ratio = '16/9';
    }

    return field;
  }

  /**
   * Дописать поле такого типа в конец JSON.
   *
   * Текст разбирается и собирается заново, поэтому отступы после вставки станут
   * такими же, как у остального. В кривой JSON дописывать нечего: возвращаем
   * false, сообщение показывает экран — тексты живут там.
   */
  function addDataField(type) {
    const text = jsonText.value.trim();

    let data = [];

    if (text !== '') {
      try {
        data = JSON.parse(text);
      } catch (error) {
        return false;
      }

      if (!Array.isArray(data)) {
        return false;
      }
    }

    // code должен быть свободен, иначе схема не сохранится
    const taken = data.map((field) => field?.code);

    let code = '';

    for (let i = 1; code === ''; i++) {
      if (!taken.includes('field' + i)) code = 'field' + i;
    }

    data.push(dataFieldTemplate(type, code));

    jsonText.value = JSON.stringify(data, null, 2);

    return true;
  }

  /**
   * Поля __data как объекты. Пока JSON не разбирается, их просто нет: его в
   * этот момент и правят, ошибку показывать рано.
   */
  const dataFields = computed(() => {
    try {
      const data = JSON.parse(jsonText.value.trim() || '[]');

      return Array.isArray(data) ? data : [];
    } catch (error) {
      return [];
    }
  });

  /**
   * Все поля ленты одним списком, как они сейчас на экране.
   *
   * Имя — то, чем поле зовётся в order: у слота физическая колонка, у поля
   * __data его code. Колонка потому, что code слота разрешено менять, а внутри
   * __data code и есть опознавательный признак.
   */
  const allFields = computed(() => {
    const list = [];

    for (const key of Object.keys(rows)) {
      for (const row of rows[key]) {
        list.push({
          name: row.column,
          type: key,
          code: row.code,
          label: row.label,
          showOnList: row.showOnList,
        });
      }
    }

    for (const field of dataFields.value) {
      list.push({
        name: field?.code ?? '',
        type: field?.type ?? '',
        code: field?.code ?? '',
        label: field?.label ?? '',
        showOnList: field?.showOnList !== false,
      });
    }

    return list;
  });

  // видимые поля в порядке order; чего в нём нет — в конец, как в схеме
  const inList = computed(() => {
    const left = new Map(allFields.value.filter((field) => field.showOnList).map((field) => [field.name, field]));

    const sorted = [];

    for (const name of order.value) {
      if (left.has(name)) {
        sorted.push(left.get(name));
        left.delete(name);
      }
    }

    return [...sorted, ...left.values()];
  });

  const notInList = computed(() => allFields.value.filter((field) => !field.showOnList));

  // флаг живёт там же, где поле: у слота в строке таблицы, у __data в JSON
  function setShowOnList(name, value) {
    for (const key of Object.keys(rows)) {
      const row = rows[key].find((row) => row.column === name);

      if (row) {
        row.showOnList = value;

        return;
      }
    }

    const data = dataFields.value;
    const field = data.find((item) => item?.code === name);

    if (!field) return;

    field.showOnList = value;

    jsonText.value = JSON.stringify(data, null, 2);
  }

  /**
   * Перенести поле: в список на нужное место или из списка вон.
   *
   * Порядок пересобирается от того, что сейчас на экране, а не правится внутри
   * order: в order могут лежать поля, которых уже нет, и позиции разошлись бы.
   */
  function moveField(name, toList, index) {
    const names = inList.value.map((field) => field.name).filter((item) => item !== name);

    if (toList) {
      names.splice(index, 0, name);
    }

    setShowOnList(name, toList);

    order.value = names;
  }

  // порядок так, как он уедет в схему: только видимые поля, все и ровно они
  function buildOrder() {
    return inList.value.map((field) => field.name);
  }

  // контейнер из текстового поля. Кривой JSON бросает исключение
  function containerField() {
    const text = jsonText.value.trim();

    if (text === '') return null;

    const data = JSON.parse(text);

    if (!Array.isArray(data)) {
      throw new Error('data must be an array');
    }

    if (data.length === 0) return null;

    return { column: '__data', code: containerCode.value, data: data };
  }

  /**
   * Поля так, как они уедут на сервер. Порядок сохраняется: поля остаются на
   * своих местах, новые дописываются в конец.
   */
  function buildFields() {
    const byColumn = new Map();

    for (const key of Object.keys(rows)) {
      for (const row of rows[key]) {
        byColumn.set(row.column, { key, row });
      }
    }

    const fields = [];

    let containerDone = false;

    for (const field of schema.value.fields ?? []) {
      if (field.column === '__data') {
        const container = containerField();

        if (container) fields.push(container);

        containerDone = true;

        continue;
      }

      const found = byColumn.get(field.column);

      if (found) {
        fields.push(toField(found.key, found.row));
        byColumn.delete(field.column);
      }
    }

    for (const { key, row } of byColumn.values()) {
      fields.push(toField(key, row));
    }

    if (!containerDone) {
      const container = containerField();

      if (container) fields.push(container);
    }

    return fields;
  }

  // ключи в объектах приходят в разном порядке, сравниваем по общему виду
  function canon(fields) {
    return JSON.stringify(
      (fields ?? []).map((field) =>
        Object.fromEntries(Object.entries(field).sort(([a], [b]) => a.localeCompare(b))),
      ),
    );
  }

  // таблицы разошлись со считанной схемой — есть что сохранять
  const dirty = computed(() => {
    try {
      if (canon(buildFields()) !== canon(schema.value.fields)) return true;

      // перетащенная колонка тоже правка, хотя поля при этом те же
      return JSON.stringify(buildOrder()) !== JSON.stringify(schema.value.order ?? []);
    } catch {
      // JSON пока не разбирается — считаем, что есть что сохранять
      return true;
    }
  });

  async function saveFeed() {
    const feed = await apiFeed({
      command: 'feedSave',
      id: feedId.value,
      code: code.value.trim(),
      title: title.value.trim(),
    });

    code.value = feed.code;
    title.value = feed.title;
  }

  async function saveSchema() {
    const fields = buildFields();
    const names = buildOrder();

    schema.value = await apiFeed({
      command: 'schemaSave',
      feedId: feedId.value,
      schema: { version: schema.value.version ?? 1, order: names, fields: fields },
    });

    order.value = Array.isArray(schema.value.order) ? schema.value.order.slice() : [];
  }

  return {
    SLOTS,
    DATA_TYPES,
    addDataField,
    feedId,
    code,
    title,
    groupId,
    itemsCount,
    schema,
    rows,
    jsonText,
    order,
    allFields,
    inList,
    notInList,
    moveField,
    groupFeeds,
    dirty,
    load,
    addRow,
    removeRow,
    describeLinks,
    fieldsOfFeed,
    saveFeed,
    saveSchema,
  };
});
