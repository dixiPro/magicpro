<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { apiMcp, apiMcpQuiet } from './api.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
import ModalWindow from '../CommonCom/ModalWindow.vue';
import McpPrompts from './mcpPrompts.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Работа с AI-агентом с одной страницы.
 *
 * Экран живёт в трёх состояниях: до запуска — выбор агента, после запуска —
 * окно агента и поле ввода, после конца — то же окно без кнопок. Сеанс уходит
 * вместе со страницей: имя сеанса нигде не хранится, и перезагрузка оставляет
 * агента на сервере — его подберёт уборка по крону.
 *
 * С сервера приходит не добавка, а весь экран целиком: агент не печатает
 * строки, а перерисовывает окно, и «добавки» у него не бывает.
 *
 * Из браузера в API едут только номера: агент и команда выбираются в списке,
 * а сами командные строки лежат в configAI.php и на экран не попадают.
 */
const config = ref({ ready: false, password: false, path: '', agents: [], pollInterval: 1000 });

const chooser = ref(false); // выбор агента, он же кнопка «старт»
const agentIndex = ref(0);
const commandIndex = ref(0);

const passwordShow = ref(false);
const password = ref('');

const instance = ref('');
const status = ref('idle'); // idle | running | stopped
const screenText = ref('');

const command = ref('');
const area = ref(null);
const screen = ref(null);

const exitShow = ref(false);

// промпты: список для вставки, редактор и выпадающий список у поля команды
const prompts = ref([]);
const promptsShow = ref(false);
const promptPick = ref(false);

/**
 * Экран построчно, с пометкой вопроса.
 *
 * Codex ставит перед набранным `›`, и в потоке ответа эта строка ничем не
 * выделяется — а искать глазами надо именно её. Разбор построчный, без разметки
 * в тексте: строка так и остаётся текстом, а жирным её делает шаблон.
 */
const lines = computed(() => {
  const rows = [];

  for (const text of screenText.value.split('\n')) {
    const empty = text.trim() === '';

    // пустые строки схлопываются в одну, а сверху не остаётся ни одной: агент
    // отбивает ими блоки, но отбивает щедро, и экран уходит в пустоту
    if (empty && (rows.length === 0 || rows[rows.length - 1].empty)) continue;

    rows.push({ text: text, empty: empty, ask: text.trimStart().startsWith('›') });
  }

  return rows;
});

const agent = computed(() => config.value.agents?.[agentIndex.value] ?? null);
const exitCommands = computed(() => agent.value?.exit ?? []);

async function loadConfig() {
  try {
    config.value = await apiMcp({ command: 'config' });
  } catch (error) {}
}

async function loadPrompts() {
  try {
    prompts.value = (await apiMcp({ command: 'prompts' })).prompts;
  } catch (error) {}
}

/**
 * Промпт в поле команды.
 *
 * Вставка, а не отправка: в промпте почти всегда остаётся дописать имя статьи
 * или номер записи. Поле не затирается — два промпта нередко нужны подряд, —
 * а список остаётся открытым по той же причине.
 */
function usePrompt(one) {
  command.value = command.value.trim() === '' ? one.text : command.value + '\n' + one.text;

  nextTick(grow);
}

/** Выбор агента: команды у каждого свои, поэтому номер команды сбрасывается. */
function chooseAgent(index) {
  agentIndex.value = Number(index);
  commandIndex.value = 0;
}

async function run() {
  try {
    const data = await apiMcp({
      command: 'start',
      agent: agentIndex.value,
      command_index: commandIndex.value,
      password: password.value,
    });

    instance.value = data.instance;
    config.value.pollInterval = data.pollInterval;

    status.value = 'running';
    screenText.value = '';

    passwordShow.value = false;
    chooser.value = false;
    password.value = '';

    document.showToast(t('mcp_started'));

    planPoll();
  } catch (error) {}
}

// —————— опрос вывода ——————

let timer = null;

function stopPoll() {
  if (timer !== null) {
    clearTimeout(timer);
    timer = null;
  }
}

function planPoll() {
  stopPoll();

  timer = setTimeout(poll, config.value.pollInterval);
}

/**
 * Новый вывод агента.
 *
 * Следующий запрос ставится только после ответа на предыдущий: медленный ответ
 * не должен собирать очередь из запросов, которые все вернут одно и то же.
 */
async function poll() {
  timer = null;

  if (status.value !== 'running') return;

  let data;

  try {
    data = await apiMcpQuiet({ command: 'output', instance: instance.value });
  } catch (error) {
    finished(error.message ?? String(error));

    return;
  }

  show(data.output);

  if (data.status !== 'running') {
    finished(data.error);

    return;
  }

  planPoll();
}

/**
 * Новый экран агента.
 *
 * Низ прилипает: пока смотрят конец, экран едет за агентом, а стоит отлистать
 * вверх — остаётся там, где его оставили.
 */
function show(text) {
  const el = screen.value;

  const stick = !el || el.scrollHeight - el.scrollTop - el.clientHeight < 40;

  screenText.value = text;

  if (stick) toBottom();
}

/** Сеанс кончился: последний вывод уже на экране, управление больше не нужно. */
function finished(error) {
  stopPoll();

  status.value = 'stopped';

  document.showToast(error ? t('mcp_stopped') + ': ' + error : t('mcp_stopped'));
}

function toBottom() {
  nextTick(() => {
    const el = screen.value;

    if (el) el.scrollTop = el.scrollHeight;
  });
}

// —————— работа с агентом ——————

async function reread() {
  try {
    const data = await apiMcp({ command: 'reread', instance: instance.value });

    screenText.value = data.output;

    toBottom();

    if (data.status !== 'running') finished('');
  } catch (error) {}
}

async function send() {
  if (command.value.trim() === '' || status.value !== 'running') return;

  try {
    await apiMcp({ command: 'send', instance: instance.value, text: command.value });

    command.value = '';

    grow();
  } catch (error) {}
}

/** Завершение: одна команда — сразу она, несколько — сперва выбор. */
function finish() {
  if (exitCommands.value.length > 1) {
    exitShow.value = true;

    return;
  }

  close(0);
}

async function close(index) {
  exitShow.value = false;

  try {
    await apiMcp({
      command: 'close',
      instance: instance.value,
      command_index: index,
    });

    document.showToast(t('mcp_closing'));
  } catch (error) {}
}

/**
 * Убить свои сеансы.
 *
 * Кнопка на случай застрявшего агента: тот держит открытым свой тред, и
 * следующий запуск в него уже не пустят. Вкладка, которая его завела, к тому
 * времени обычно закрыта, и завершить его по-хорошему некому.
 */
async function killAll() {
  if (!(await document.confirmDialog(t('mcp_kill_confirm')))) return;

  try {
    const data = await apiMcp({ command: 'killAll' });

    document.showToast(t('mcp_killed') + ': ' + data.killed);

    if (status.value !== 'idle') {
      stopPoll();

      status.value = 'idle';
      screenText.value = '';
      instance.value = '';
    }
  } catch (error) {}
}

// —————— поле ввода ——————

/**
 * Высота по содержимому, но не ниже трёх строк.
 *
 * Сначала `auto`, потом `scrollHeight`: без сброса высота умеет только расти.
 */
function grow() {
  const el = area.value;

  if (!el) return;

  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

// Enter переносит строку, отправляет только ctrl-enter: команда агенту чаще
// многострочная, чем нет, и отправленный по Enter обрывок не отозвать
function onKeydown(event) {
  if (event.key !== 'Enter' || !(event.ctrlKey || event.metaKey)) return;

  event.preventDefault();

  send();
}

/**
 * Уход со страницы при живом агенте.
 *
 * Закрыть сеанс здесь уже нельзя — запрос не успеет, — но предупредить можно.
 * Ушедшего всё равно подберёт уборка по крону, только не сразу.
 */
function onLeave(event) {
  if (status.value !== 'running') return;

  event.preventDefault();

  event.returnValue = t('mcp_leave');

  return event.returnValue;
}

onMounted(() => {
  loadConfig();
  loadPrompts();

  window.addEventListener('beforeunload', onLeave);
});

onBeforeUnmount(() => {
  stopPoll();

  window.removeEventListener('beforeunload', onLeave);
});
</script>

<template>
  <div class="my-3">
    <TosatConfirm />

    <h1 class="h4 mb-3">MCP</h1>

    <!--
      Убить свои сеансы. Своё условие, а не звено цепочки v-if ниже: цепочка
      выбирает одно из состояний экрана, а кнопка живёт рядом с любым из них,
      кроме работающего сеанса — там для этого есть «Завершить».
    -->
    <div
      v-if="config.ready && config.password && config.agents.length && status !== 'running'"
      class="float-end"
    >
      <button class="btn btn-sm btn-outline-danger" :title="t('mcp_kill_help')" @click="killAll()">
        <i class="fas fa-skull"></i>
        {{ t('mcp_kill_all') }}
      </button>
    </div>

    <div v-if="!config.ready" class="text-danger">
      {{ t('mcp_no_config') }}
      <code>{{ config.path }}</code>
    </div>

    <!-- пустой пароль — это выключенный раздел, а не забытая настройка -->
    <div v-else-if="!config.password" class="text-danger">
      {{ t('mcp_no_password') }}
      <code>{{ config.path }}</code>
    </div>

    <div v-else-if="!config.agents.length" class="text-danger">{{ t('mcp_no_agents') }}</div>

    <!-- редактор промптов: и до запуска, и при живом сеансе -->
    <McpPrompts
      v-else-if="promptsShow"
      :prompts="prompts"
      @saved="prompts = $event"
      @close="promptsShow = false"
    />

    <!-- выбор агента: до запуска и только один раз за сеанс -->
    <template v-else-if="status === 'idle'">
      <button v-if="!chooser" class="btn btn-sm btn-primary" @click="chooser = true">
        <i class="fas fa-play"></i>
        {{ t('mcp_start') }}
      </button>

      <button v-if="!chooser" class="btn btn-sm btn-outline-secondary ms-2" @click="promptsShow = true">
        <i class="fas fa-list"></i>
        {{ t('mcp_prompts') }}
      </button>

      <div v-else class="d-flex gap-2 align-items-end flex-wrap" style="max-width: 60rem">
        <div>
          <label class="form-label small mb-1">{{ t('mcp_agent') }}</label>
          <select
            :value="agentIndex"
            class="form-select form-select-sm w-auto"
            @change="chooseAgent($event.target.value)"
          >
            <option v-for="(one, index) in config.agents" :key="index" :value="index">
              {{ one.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="form-label small mb-1">{{ t('mcp_command') }}</label>
          <select v-model="commandIndex" class="form-select form-select-sm w-auto">
            <option v-for="(label, index) in agent?.start ?? []" :key="index" :value="index">
              {{ label }}
            </option>
          </select>
        </div>

        <button
          v-if="agent?.rules"
          class="btn btn-sm btn-primary"
          @click="passwordShow = true"
        >
          {{ t('mcp_run') }}
        </button>
      </div>

      <!-- правила агента: без них запуск не даётся, и это видно до нажатия -->
      <div v-if="chooser && agent && !agent.rules" class="text-danger mt-2">
        {{ t('mcp_no_rules') }}
        <code>{{ agent.rulesPath }}</code>
      </div>
    </template>

    <!-- окно агента: живое и законченное отличаются только кнопками -->
    <template v-else>
      <div class="d-flex gap-2 align-items-center mb-2">
        <button v-if="status === 'running'" class="btn btn-sm btn-outline-secondary" :title="t('mcp_reread_help')" @click="reread()">
          <i class="fas fa-rotate"></i>
          {{ t('mcp_reread') }}
        </button>

        <span class="text-muted small">{{ t('mcp_session') }} {{ instance }}</span>

        <span v-if="status === 'stopped'" class="text-danger small">{{ t('mcp_stopped') }}</span>
      </div>

      <pre ref="screen" class="agentScreen border rounded p-2"><template v-for="(line, index) in lines" :key="index"><b v-if="line.ask">{{ line.text }}</b><span v-else>{{ line.text }}</span>{{ '\n' }}</template></pre>

      <template v-if="status === 'running'">
        <textarea
          ref="area"
          v-model="command"
          rows="3"
          class="form-control form-control-sm"
          style="overflow: hidden; resize: none"
          @input="grow()"
          @keydown="onKeydown"
        ></textarea>

        <div class="form-text">{{ t('mcp_send_hint') }}</div>

        <div class="mt-2">
          <button class="btn btn-sm btn-primary" @click="send()">
            <i class="fas fa-paper-plane"></i>
            {{ t('mcp_send') }}
          </button>

          <button
            class="btn btn-sm btn-outline-secondary ms-2"
            :class="{ active: promptPick }"
            @click="promptPick = !promptPick"
          >
            <i class="fas fa-list"></i>
            {{ t('mcp_prompts') }}
          </button>

          <button class="btn btn-sm btn-outline-danger ms-2" @click="finish()">
            {{ t('mcp_finish') }}
          </button>
        </div>

        <!-- список промптов: клик вставляет, окно не закрывается -->
        <div v-if="promptPick" class="border rounded mt-2 p-2" style="max-width: 60rem">
          <div v-if="!prompts.length" class="text-muted small">{{ t('mcp_prompts_empty') }}</div>

          <button
            v-for="(one, index) in prompts"
            :key="index"
            class="btn btn-sm btn-link text-decoration-none d-block text-start p-0"
            :title="one.text"
            @click="usePrompt(one)"
          >
            {{ one.name }}
          </button>

          <button class="btn btn-sm btn-outline-secondary mt-2" @click="promptsShow = true">
            <i class="fas fa-pen"></i>
            {{ t('edit') }}
          </button>
        </div>
      </template>
    </template>

    <!-- пароль: спрашивается на каждый запуск, в браузере не остаётся -->
    <ModalWindow
      v-model:visible="passwordShow"
      :header="t('mcp_password_title')"
      :initial-width="420"
      :initial-height="220"
    >
      <div class="mb-3">
        <label class="form-label">{{ t('mcp_password') }}</label>
        <input v-model="password" type="password" class="form-control form-control-sm" @keydown.enter="run()" />
      </div>

      <div class="text-end">
        <button class="btn btn-sm btn-outline-secondary" @click="passwordShow = false">{{ t('cancel') }}</button>
        <button class="btn btn-sm btn-primary ms-2" @click="run()">{{ t('mcp_run') }}</button>
      </div>
    </ModalWindow>

    <!-- чем завершать: окно только когда команд больше одной -->
    <ModalWindow
      v-model:visible="exitShow"
      :header="t('mcp_finish_title')"
      :initial-width="420"
      :initial-height="260"
    >
      <button
        v-for="(label, index) in exitCommands"
        :key="index"
        class="btn btn-sm btn-outline-danger d-block mb-2"
        @click="close(index)"
      >
        {{ label }}
      </button>
    </ModalWindow>
  </div>
</template>

<style scoped>
.agentScreen {
  height: 60vh;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-word;
  background: var(--bs-body-bg);
  /* пропорциональный шрифт: рамки и колонки агента поедут, зато текст читается */
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', 'Noto Sans',
    'Liberation Sans', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
  font-size: 16px;
  line-height: 120%;
}
</style>
