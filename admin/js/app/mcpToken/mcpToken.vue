<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const state = ref({ exists: false });

// показывается один раз, сразу после выдачи: на сервере остался только хеш
const token = ref('');
const apiActive = ref(false);

const URL = '/a_dmin/api/mcpToken';

const mcpUrl = computed(() => window.location.origin + '/mcp/magicpro');

async function api(data) {
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    const response = await apiCall({
      url: URL,
      data: data,
      logResult: false,
    });
    return response.data;
  } catch (e) {
    document.showToast(e, 'error');
    throw new Error(t('error'));
  } finally {
    apiActive.value = false;
  }
}

async function load() {
  state.value = await api({ command: 'state' });
}

async function issue() {
  // второй токен не заводится: новый затирает старый, и агент с прежним встанет
  if (state.value.exists && !(await document.confirmDialog(t('mcp_reissue_confirm')))) return;

  const answer = await api({ command: 'issue' });

  token.value = answer.token;
  state.value = answer.state;
}

async function revoke() {
  if (!(await document.confirmDialog(t('mcp_revoke_confirm')))) return;

  const answer = await api({ command: 'revoke' });

  token.value = '';
  state.value = answer.state;

  document.showToast(t('mcp_revoked'));
}

async function copy(text) {
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
    } else {
      // сайт открыт по http, а clipboard живёт только в защищённом контексте:
      // остаётся старый способ через выделение
      const area = document.createElement('textarea');

      area.value = text;
      area.style.position = 'fixed';
      area.style.opacity = '0';

      document.body.appendChild(area);
      area.select();
      document.execCommand('copy');
      area.remove();
    }

    document.showToast(t('mcp_copied'));
  } catch (e) {
    document.showToast(e.message ?? e, 'error');
  }
}

onMounted(load);
</script>

<template>
  <div class="my-3">
    <TosatConfirm />

    <div class="p-3 border rounded">
      <div class="fw-bold mb-1">{{ t('mcp_title') }}</div>
      <div class="form-text mb-3">{{ t('mcp_intro') }}</div>

      <div v-if="!state.exists" class="text-muted mb-3">{{ t('mcp_no_token') }}</div>

      <table v-else class="table table-sm small w-auto mb-3">
        <tbody>
          <tr>
            <td>{{ t('mcp_issued') }}</td>
            <td>{{ state.created_at }}</td>
          </tr>
          <tr>
            <td>{{ t('mcp_last_used') }}</td>
            <td>{{ state.last_used_at }}</td>
          </tr>
          <tr>
            <td>{{ t('mcp_ip') }}</td>
            <td>{{ state.ip }}</td>
          </tr>
          <tr>
            <td>{{ t('mcp_state') }}</td>
            <td :class="state.alive ? 'text-success' : 'text-danger'">
              <template v-if="state.alive">
                {{ t('mcp_alive', { idle: state.idle_left, life: state.life_left }) }}
              </template>
              <template v-else>{{ t('mcp_dead') }}</template>
            </td>
          </tr>
        </tbody>
      </table>

      <button class="btn btn-sm btn-primary" :disabled="apiActive" @click="issue">
        <i class="fas fa-key"></i>
        {{ state.exists ? t('mcp_get_again') : t('mcp_get') }}
      </button>

      <button v-if="state.exists" class="btn btn-sm btn-outline-danger ms-2" :disabled="apiActive" @click="revoke">
        <i class="fas fa-ban"></i>
        {{ t('mcp_revoke') }}
      </button>
    </div>

    <div v-if="token" class="p-3 border rounded mt-3 bg-light">
      <div class="fw-bold text-danger mb-2">{{ t('mcp_once') }}</div>

      <div class="input-group input-group-sm mb-2">
        <input class="form-control font-monospace" readonly :value="token" />
        <button class="btn btn-outline-secondary" @click="copy(token)">
          <i class="fas fa-copy"></i>
          {{ t('mcp_copy') }}
        </button>
      </div>
    </div>

    <div class="p-3 border rounded mt-3">
      <div class="input-group input-group-sm mb-2">
        <span class="input-group-text">{{ t('mcp_url') }}</span>
        <input class="form-control font-monospace" readonly :value="mcpUrl" />
        <button class="btn btn-outline-secondary" @click="copy(mcpUrl)">
          <i class="fas fa-copy"></i>
        </button>
      </div>
    </div>


    <div class="form-text mt-3">{{ t('mcp_export_hint') }}</div>
  </div>
</template>
