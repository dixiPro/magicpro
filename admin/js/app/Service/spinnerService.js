import { createVNode, render } from 'vue';
import Spinner from './Spinner.vue';

let spinnerId = 0;
const instances = new Map();

export function showSpinner() {
  const id = ++spinnerId;

  const container = document.createElement('div');
  document.body.appendChild(container);

  const vnode = createVNode(Spinner);
  render(vnode, container);

  instances.set(id, container);

  return id;
}

export function spinnerHide(id) {
  const container = instances.get(id);
  if (!container) return;

  render(null, container); // размонтировать компонент
  container.remove(); // удалить DOM
  instances.delete(id);
}
