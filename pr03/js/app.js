// app.js — собственный JavaScript административной панели.
// Bootstrap JS отвечает за поведение компонентов (collapse, modal, offcanvas, toast, accordion).
// Этот файл загружает dataset, строит из него интерфейс и обрабатывает действия пользователя.

'use strict';

const DATA_URLS = {
  dashboard: 'data/dashboard.json',
  notifications: 'data/notifications.json',
};

// Внешний вид статусов из dashboard.json → rows[].status
const STATUS_STYLES = {
  'Новая': { badge: 'secondary', description: 'Действие создано, но ещё не взято в работу.' },
  'В работе': { badge: 'warning', description: 'Действие выполняется ответственным сотрудником.' },
  'Готово': { badge: 'success', description: 'Действие завершено, изменений не требуется.' },
};

// Цвет карточки показателя по её порядковому номеру в metrics
const METRIC_COLORS = ['primary', 'success', 'danger', 'info'];

const state = {
  metrics: [],
  rows: [],
  notifications: [],
  filter: 'all',
};

// ---------- Вспомогательные функции ----------

const $ = (selector) => document.querySelector(selector);

// Создание элемента с классом и текстом (textContent защищает от HTML-инъекций из формы)
function el(tag, className = '', text = '') {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== '') node.textContent = text;
  return node;
}

function statusStyle(status) {
  return STATUS_STYLES[status] || { badge: 'light', description: '' };
}

function statusList() {
  // Статусы берутся из данных, порядок — как в STATUS_STYLES
  const fromRows = new Set(state.rows.map((row) => row.status));
  return Object.keys(STATUS_STYLES).filter((s) => fromRows.has(s))
    .concat([...fromRows].filter((s) => !(s in STATUS_STYLES)));
}

async function fetchJson(url) {
  const response = await fetch(url);
  if (!response.ok) throw new Error(`${url}: HTTP ${response.status}`);
  return response.json();
}

// ---------- Загрузка данных ----------

async function loadData() {
  try {
    const [dashboard, notifications] = await Promise.all([
      fetchJson(DATA_URLS.dashboard),
      fetchJson(DATA_URLS.notifications),
    ]);
    state.metrics = Array.isArray(dashboard.metrics) ? dashboard.metrics : [];
    state.rows = Array.isArray(dashboard.rows) ? [...dashboard.rows] : [];
    // id нет в notifications.json — используем индекс как устойчивый ключ
    state.notifications = (Array.isArray(notifications) ? notifications : [])
      .map((n, index) => ({ ...n, id: index + 1, read: false }));
    renderAll();
  } catch (error) {
    showLoadError(error);
  }
}

function showLoadError(error) {
  const box = $('#loadError');
  box.classList.remove('d-none');
  box.replaceChildren(
    el('strong', '', 'Не удалось загрузить данные. '),
    el('span', '', 'Откройте проект через локальный сервер (см. README: '),
    el('code', '', 'python -m http.server 8000'),
    el('span', '', `). Подробности: ${error.message}`),
  );
  $('#projectMeta').textContent = 'Данные недоступны';
  $('#activityBody').replaceChildren(emptyRow('Нет данных для отображения'));
  console.warn('[app] Ошибка загрузки dataset:', error);
}

// ---------- Отрисовка ----------

function renderAll() {
  renderSummary();
  renderMetrics();
  renderStatusOptions();
  renderRows();
  renderStatusAccordion();
  renderNotifications();
}

function renderSummary() {
  $('#projectMeta').textContent =
    `Показателей: ${state.metrics.length} · действий в журнале: ${state.rows.length} · уведомлений: ${state.notifications.length}`;

  // Метрика «Готовность» хранится строкой "92%" — превращаем в число для progress bar
  const readiness = state.metrics.find((m) => m.title === 'Готовность');
  const percent = readiness ? Math.min(100, Math.max(0, parseInt(readiness.value, 10) || 0)) : 0;
  const bar = $('#progressBar');
  bar.setAttribute('aria-valuenow', String(percent));
  bar.querySelector('.progress-bar').style.width = `${percent}%`;
  $('#progressLabel').textContent = `${percent}%`;
}

function renderMetrics() {
  const row = $('#metricsRow');
  if (state.metrics.length === 0) {
    row.replaceChildren(el('p', 'text-body-secondary', 'Показатели отсутствуют.'));
    return;
  }
  const cols = state.metrics.map((metric, index) => {
    const color = METRIC_COLORS[index % METRIC_COLORS.length];
    const col = el('div', 'col-12 col-sm-6 col-xl-3');
    const card = el('article', `card metric-card h-100 shadow-sm border-${color}`);
    card.id = `metric-${index + 1}`;
    const body = el('div', 'card-body');
    body.append(
      el('h3', 'h6 text-body-secondary mb-2', metric.title),
      el('p', `metric-value js-metric-value text-${color} mb-0`, String(metric.value)),
    );
    card.append(body);
    col.append(card);
    return col;
  });
  row.replaceChildren(...cols);
}

// Счётчик метрики «Заявки» увеличивается при добавлении новой записи
function incrementMetric(title) {
  const index = state.metrics.findIndex((m) => m.title === title);
  if (index === -1 || typeof state.metrics[index].value !== 'number') return;
  state.metrics[index].value += 1;
  const node = document.querySelector(`#metric-${index + 1} .js-metric-value`);
  if (node) node.textContent = String(state.metrics[index].value);
}

function renderStatusOptions() {
  const filter = $('#statusFilter');
  const formSelect = $('#recordStatus');
  statusList().forEach((status) => {
    const a = el('option', '', status);
    a.value = status;
    filter.append(a);
    const b = el('option', '', status);
    b.value = status;
    formSelect.append(b);
  });
}

function emptyRow(text) {
  const tr = el('tr');
  const td = el('td', 'text-center text-body-secondary py-4', text);
  td.colSpan = 3;
  tr.append(td);
  return tr;
}

function tableRow(item) {
  const tr = el('tr');
  tr.dataset.id = String(item.id);
  tr.append(el('td', 'text-body-secondary', `#${item.id}`), el('td', '', item.action));
  const statusCell = el('td');
  statusCell.append(el('span', `badge text-bg-${statusStyle(item.status).badge}`, item.status));
  tr.append(statusCell);
  return tr;
}

function renderRows(highlightId = null) {
  const items = state.filter === 'all'
    ? state.rows
    : state.rows.filter((item) => item.status === state.filter);

  const body = $('#activityBody');
  body.replaceChildren(...(items.length ? items.map(tableRow) : [emptyRow('Нет записей с выбранным статусом')]));

  if (highlightId !== null) {
    body.querySelector(`tr[data-id="${highlightId}"]`)?.classList.add('row-new');
  }
  $('#activityCount').textContent = `Показано записей: ${items.length} из ${state.rows.length}`;
}

function renderStatusAccordion() {
  const accordion = $('#statusAccordion');
  const items = statusList().map((status, index) => {
    const id = `status-${index + 1}`;
    const item = el('div', 'accordion-item');
    const header = el('h3', 'accordion-header');
    const button = el('button', `accordion-button${index === 0 ? '' : ' collapsed'}`);
    button.type = 'button';
    button.dataset.bsToggle = 'collapse';
    button.dataset.bsTarget = `#${id}`;
    button.setAttribute('aria-expanded', String(index === 0));
    button.setAttribute('aria-controls', id);
    button.append(el('span', `badge text-bg-${statusStyle(status).badge} me-2`, status));
    header.append(button);

    const collapse = el('div', `accordion-collapse collapse${index === 0 ? ' show' : ''}`);
    collapse.id = id;
    collapse.dataset.bsParent = '#statusAccordion';
    const count = state.rows.filter((r) => r.status === status).length;
    collapse.append(el('div', 'accordion-body small', `${statusStyle(status).description} Записей: ${count}.`));

    item.append(header, collapse);
    return item;
  });
  accordion.replaceChildren(...items);
}

function renderNotifications() {
  const list = $('#notificationsList');
  if (state.notifications.length === 0) {
    list.replaceChildren(el('p', 'text-body-secondary', 'Уведомлений нет.'));
  } else {
    list.replaceChildren(...state.notifications.map((n) => {
      const item = el('div', `list-group-item list-group-item-${n.type}${n.read ? '' : ' notification-unread'}`);
      item.dataset.id = String(n.id);
      item.append(el('span', '', n.text));
      if (!n.read) item.append(el('span', 'badge text-bg-primary ms-2', 'новое'));
      return item;
    }));
  }
  updateUnreadCounter();
}

function updateUnreadCounter() {
  const unread = state.notifications.filter((n) => !n.read).length;
  const badge = $('#unreadCount');
  badge.textContent = String(unread);
  badge.classList.toggle('d-none', unread === 0);
  $('#markAllRead').textContent = unread === 0 ? 'Вернуть как непрочитанные' : 'Отметить все прочитанными';
}

// ---------- Toast (программное управление Bootstrap-компонентом) ----------

const TOAST_TITLES = { success: 'Готово', warning: 'Внимание', danger: 'Ошибка', info: 'Информация' };

function notificationByType(type) {
  return state.notifications.find((n) => n.type === type);
}

function showToast(type, text) {
  $('#toastTitle').textContent = TOAST_TITLES[type] || 'Уведомление';
  $('#toastBody').textContent = text;
  $('#toastTime').textContent = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
  $('#toastDot').className = `toast-dot rounded-circle me-2 bg-${type}`;
  bootstrap.Toast.getOrCreateInstance($('#appToast'), { delay: 4000 }).show();
}

// ---------- Валидация ----------

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Собственная проверка email: is-invalid при ошибке, is-valid при успехе
function validateEmail() {
  const input = $('#ownerEmail');
  const value = input.value.trim();
  let error = '';
  if (value === '') error = 'Введите email ответственного.';
  else if (!EMAIL_PATTERN.test(value)) error = 'Неверный формат email, пример: admin@example.test';

  $('#ownerEmailError').textContent = error;
  input.classList.toggle('is-invalid', error !== '');
  input.classList.toggle('is-valid', error === '');
  return error === '';
}

function validateField(input) {
  const ok = input.checkValidity() && input.value.trim() !== '';
  input.classList.toggle('is-invalid', !ok);
  input.classList.toggle('is-valid', ok);
  return ok;
}

function validateForm() {
  const results = [validateField($('#recordAction')), validateField($('#recordStatus')), validateEmail()];
  return results.every(Boolean);
}

function resetForm() {
  const form = $('#recordForm');
  form.reset();
  form.querySelectorAll('.is-valid, .is-invalid').forEach((node) => node.classList.remove('is-valid', 'is-invalid'));
}

// ---------- Обработчики событий ----------

// 1. Сохранение формы: валидация → новая строка → закрытие modal → toast из notifications.json
function onRecordSubmit(event) {
  event.preventDefault();
  if (!validateForm()) {
    const warning = notificationByType('warning');
    showToast('warning', warning ? warning.text : 'Проверьте обязательные поля');
    $('#recordForm .is-invalid')?.focus();
    return;
  }

  const nextId = state.rows.reduce((max, row) => Math.max(max, Number(row.id) || 0), 0) + 1;
  const record = { id: nextId, action: $('#recordAction').value.trim(), status: $('#recordStatus').value };
  state.rows.unshift(record);
  incrementMetric('Заявки');
  renderRows(record.id);
  renderStatusAccordion();
  renderSummary();

  bootstrap.Modal.getOrCreateInstance($('#recordModal')).hide();
  const success = notificationByType('success');
  showToast('success', `${success ? success.text : 'Изменения сохранены'}: «${record.action}» (${record.status}).`);
}

// 2. Фильтр таблицы по статусу
function onFilterChange(event) {
  state.filter = event.target.value;
  renderRows();
}

// 3. Переключение «все прочитаны / вернуть непрочитанные» + обновление счётчика
function onMarkAllRead() {
  const hasUnread = state.notifications.some((n) => !n.read);
  state.notifications.forEach((n) => { n.read = hasUnread; });
  renderNotifications();
}

function bindEvents() {
  $('#recordForm').addEventListener('submit', onRecordSubmit);
  $('#statusFilter').addEventListener('change', onFilterChange);
  $('#markAllRead').addEventListener('click', onMarkAllRead);

  // 4. Живая проверка email и остальных полей
  $('#ownerEmail').addEventListener('input', validateEmail);
  $('#recordAction').addEventListener('input', (event) => validateField(event.target));
  $('#recordStatus').addEventListener('change', (event) => validateField(event.target));

  // 5. События Bootstrap: сброс формы после закрытия modal, фокус при открытии
  $('#recordModal').addEventListener('hidden.bs.modal', resetForm);
  $('#recordModal').addEventListener('shown.bs.modal', () => $('#recordAction').focus());

  // 6. Закрываем мобильное меню после выбора пункта
  document.querySelectorAll('#mainNav .nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      const nav = $('#mainNav');
      if (nav.classList.contains('show')) bootstrap.Collapse.getOrCreateInstance(nav).hide();
    });
  });
}

// ---------- Старт ----------

document.addEventListener('DOMContentLoaded', () => {
  if (typeof bootstrap === 'undefined') {
    showLoadError(new Error('bootstrap is not defined — не подключён bootstrap.bundle.min.js'));
    return;
  }
  bindEvents();
  loadData();
});
