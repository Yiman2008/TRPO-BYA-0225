// app.js — собственный JavaScript панели.
// Bootstrap JS отвечает за поведение компонентов (collapse, modal, offcanvas, toast, accordion).
// Этот файл загружает dataset, строит из него интерфейс и обрабатывает действия пользователя.

'use strict';

const DATA_URLS = {
  dashboard: 'data/dashboard.json',
  notifications: 'data/notifications.json',
};

const state = {
  dashboard: null,
  notifications: [],
  activity: [],
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

function formatDate(iso) {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatDateTime(iso) {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleString('ru-RU', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function statusInfo(statusId) {
  return state.dashboard.statuses[statusId] || { label: statusId, badge: 'secondary' };
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
    state.dashboard = dashboard;
    state.activity = [...dashboard.activity];
    state.notifications = [...notifications].sort((a, b) => b.time.localeCompare(a.time));
    renderAll();
    showFirstUnreadNotification();
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
  $('#projectTitle').textContent = 'Данные недоступны';
  $('#activityBody').replaceChildren(emptyRow('Нет данных для отображения'));
  console.warn('[app] Ошибка загрузки dataset:', error);
}

// ---------- Отрисовка ----------

function renderAll() {
  renderHeader();
  renderMetrics();
  renderStatusOptions();
  renderActivity();
  renderUpcoming();
  renderStatusAccordion();
  renderNotifications();
  renderFormOptions();
}

function renderHeader() {
  const { project, course, semester, updatedAt, user } = state.dashboard;
  document.title = `${project} — ПР-03`;
  $('#projectTitle').textContent = project;
  $('#projectMeta').textContent = `${course} · ${semester} · обновлено ${formatDate(updatedAt)}`;
  $('#userName').textContent = user.name;
  $('#userAvatar').src = user.avatar;
  updateProgress();
}

function metricById(id) {
  return state.dashboard.metrics.find((metric) => metric.id === id);
}

function updateProgress() {
  const total = metricById('labs-total')?.value || 0;
  const done = metricById('labs-done')?.value || 0;
  const percent = total ? Math.round((done / total) * 100) : 0;
  const bar = $('#progressBar');
  bar.setAttribute('aria-valuenow', String(percent));
  bar.querySelector('.progress-bar').style.width = `${percent}%`;
  $('#progressLabel').textContent = `${done} из ${total} · ${percent}%`;
}

function renderMetrics() {
  const row = $('#metricsRow');
  const cols = state.dashboard.metrics.map((metric) => {
    const col = el('div', 'col-12 col-sm-6 col-xl');
    const card = el('article', `card metric-card h-100 shadow-sm border-${metric.color}`);
    card.id = `metric-${metric.id}`;
    const body = el('div', 'card-body');
    const title = el('h3', 'h6 text-body-secondary mb-2', metric.title);
    const value = el('p', `metric-value text-${metric.color} mb-1`);
    value.append(el('span', 'js-metric-value', String(metric.value)));
    if (metric.unit) value.append(el('small', 'fs-6 fw-normal text-body-secondary ms-1', metric.unit));
    const hint = el('p', 'small text-body-secondary mb-0', metric.hint);
    body.append(title, value, hint);
    card.append(body);
    col.append(card);
    return col;
  });
  row.replaceChildren(...cols);
}

function setMetricValue(id, value) {
  const metric = metricById(id);
  if (!metric) return;
  metric.value = value;
  const node = document.querySelector(`#metric-${id} .js-metric-value`);
  if (node) node.textContent = String(value);
}

function renderStatusOptions() {
  const select = $('#statusFilter');
  Object.entries(state.dashboard.statuses).forEach(([id, status]) => {
    const option = el('option', '', status.label);
    option.value = id;
    select.append(option);
  });
}

function emptyRow(text) {
  const tr = el('tr');
  const td = el('td', 'text-center text-body-secondary py-4', text);
  td.colSpan = 5;
  tr.append(td);
  return tr;
}

function activityRow(item) {
  const status = statusInfo(item.status);
  const tr = el('tr');
  tr.dataset.id = item.id;
  tr.append(
    el('td', 'text-nowrap-date', formatDate(item.date)),
    el('td', 'fw-semibold', item.lab),
    el('td', '', item.title),
    el('td', '', item.action),
  );
  const statusCell = el('td');
  statusCell.append(el('span', `badge text-bg-${status.badge}`, status.label));
  tr.append(statusCell);
  return tr;
}

function renderActivity(highlightId = null) {
  const items = state.filter === 'all'
    ? state.activity
    : state.activity.filter((item) => item.status === state.filter);

  const body = $('#activityBody');
  if (items.length === 0) {
    body.replaceChildren(emptyRow('Нет записей с выбранным статусом'));
  } else {
    body.replaceChildren(...items.map(activityRow));
  }

  if (highlightId) {
    body.querySelector(`tr[data-id="${highlightId}"]`)?.classList.add('row-new');
  }

  $('#activityCount').textContent = `Показано записей: ${items.length} из ${state.activity.length}`;
}

function renderUpcoming() {
  const list = $('#upcomingList');
  const items = state.dashboard.upcoming.map((item) => {
    const li = el('li', 'list-group-item d-flex justify-content-between align-items-start gap-2');
    const text = el('div');
    text.append(el('div', 'fw-semibold', item.lab), el('div', 'small text-body-secondary', item.title));
    const badge = el('span', 'badge text-bg-light border text-nowrap', `до ${formatDate(item.deadline)}`);
    li.append(text, badge);
    return li;
  });
  list.replaceChildren(...items);
}

const STATUS_DESCRIPTIONS = {
  accepted: 'Работа защищена, оценка выставлена. Повторная сдача не требуется.',
  review: 'Ссылка на репозиторий отправлена, преподаватель ещё не проверил работу.',
  rework: 'Преподаватель вернул работу с замечаниями — исправьте и отправьте снова.',
  progress: 'Работа начата, но ещё не отправлена на проверку.',
};

function renderStatusAccordion() {
  const accordion = $('#statusAccordion');
  const items = Object.entries(state.dashboard.statuses).map(([id, status], index) => {
    const item = el('div', 'accordion-item');
    const header = el('h3', 'accordion-header');
    const button = el('button', `accordion-button${index === 0 ? '' : ' collapsed'}`);
    button.type = 'button';
    button.dataset.bsToggle = 'collapse';
    button.dataset.bsTarget = `#status-${id}`;
    button.setAttribute('aria-expanded', String(index === 0));
    button.setAttribute('aria-controls', `status-${id}`);
    button.append(el('span', `badge text-bg-${status.badge} me-2`, status.label));
    header.append(button);

    const collapse = el('div', `accordion-collapse collapse${index === 0 ? ' show' : ''}`);
    collapse.id = `status-${id}`;
    collapse.dataset.bsParent = '#statusAccordion';
    const count = state.activity.filter((a) => a.status === id).length;
    const body = el('div', 'accordion-body small', `${STATUS_DESCRIPTIONS[id] || ''} Записей в журнале: ${count}.`);
    collapse.append(body);

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
    const items = state.notifications.map((n) => {
      const item = el('div', `list-group-item${n.read ? '' : ' notification-unread'}`);
      item.dataset.id = n.id;
      const top = el('div', 'd-flex justify-content-between gap-2');
      top.append(
        el('strong', `text-${n.type}-emphasis`, n.title),
        el('small', 'text-body-secondary text-nowrap', formatDateTime(n.time)),
      );
      item.append(top, el('p', 'small mb-0 mt-1', n.message));
      return item;
    });
    list.replaceChildren(...items);
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

function renderFormOptions() {
  const labs = new Map();
  [...state.dashboard.activity, ...state.dashboard.upcoming].forEach((item) => {
    if (!labs.has(item.lab)) labs.set(item.lab, item.title);
  });
  const labSelect = $('#recordLab');
  [...labs.entries()].sort(([a], [b]) => a.localeCompare(b, 'ru')).forEach(([lab, title]) => {
    const option = el('option', '', `${lab} — ${title}`);
    option.value = lab;
    labSelect.append(option);
  });

  const statusSelect = $('#recordStatus');
  Object.entries(state.dashboard.statuses).forEach(([id, status]) => {
    const option = el('option', '', status.label);
    option.value = id;
    statusSelect.append(option);
  });
}

// ---------- Toast (программное управление Bootstrap-компонентом) ----------

function showToast({ title, message, type = 'primary', time = new Date().toISOString() }) {
  $('#toastTitle').textContent = title;
  $('#toastBody').textContent = message;
  $('#toastTime').textContent = formatDateTime(time);
  $('#toastDot').className = `toast-dot rounded-circle me-2 bg-${type}`;
  bootstrap.Toast.getOrCreateInstance($('#appToast'), { delay: 5000 }).show();
}

function showFirstUnreadNotification() {
  const first = state.notifications.find((n) => !n.read);
  if (first) {
    showToast({ title: first.title, message: first.message, type: first.type, time: first.time });
  }
}

// ---------- Валидация ----------

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

// Собственная проверка email: is-invalid при ошибке, is-valid при успехе
function validateEmail() {
  const input = $('#reviewerEmail');
  const value = input.value.trim();
  let error = '';
  if (value === '') error = 'Введите email преподавателя.';
  else if (!EMAIL_PATTERN.test(value)) error = 'Неверный формат email, пример: teacher@example.com';

  $('#reviewerEmailError').textContent = error;
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
  const fields = ['#recordLab', '#recordAction', '#recordStatus', '#recordDate'].map($);
  const results = fields.map(validateField);
  results.push(validateEmail());
  return results.every(Boolean);
}

function resetForm() {
  const form = $('#recordForm');
  form.reset();
  form.querySelectorAll('.is-valid, .is-invalid').forEach((node) => node.classList.remove('is-valid', 'is-invalid'));
  $('#recordDate').value = new Date().toISOString().slice(0, 10);
}

// ---------- Обработчики событий ----------

// 1. Сохранение формы: валидация → новая запись → закрытие modal → toast
function onRecordSubmit(event) {
  event.preventDefault();
  if (!validateForm()) {
    $('#recordForm .is-invalid')?.focus();
    return;
  }

  const lab = $('#recordLab').value;
  const known = [...state.dashboard.activity, ...state.dashboard.upcoming].find((item) => item.lab === lab);
  const record = {
    id: `act-${Date.now()}`,
    date: $('#recordDate').value,
    lab,
    title: known ? known.title : lab,
    action: $('#recordAction').value.trim(),
    status: $('#recordStatus').value,
  };

  state.activity.unshift(record);
  if (record.status === 'review') setMetricValue('labs-review', metricById('labs-review').value + 1);
  if (record.status === 'accepted') {
    setMetricValue('labs-done', metricById('labs-done').value + 1);
    updateProgress();
  }
  renderActivity(record.id);
  renderStatusAccordion();

  bootstrap.Modal.getOrCreateInstance($('#recordModal')).hide();
  showToast({
    title: 'Запись добавлена',
    message: `${record.lab}: «${record.action}» — ${statusInfo(record.status).label}.`,
    type: 'success',
  });
}

// 2. Фильтр журнала по статусу
function onFilterChange(event) {
  state.filter = event.target.value;
  renderActivity();
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

  // 4. Живая проверка email (после первого ввода)
  $('#reviewerEmail').addEventListener('input', validateEmail);
  ['#recordLab', '#recordAction', '#recordStatus', '#recordDate'].forEach((selector) => {
    $(selector).addEventListener('change', (event) => validateField(event.target));
  });

  // 5. События Bootstrap: сброс формы после закрытия modal, фокус при открытии
  $('#recordModal').addEventListener('hidden.bs.modal', resetForm);
  $('#recordModal').addEventListener('shown.bs.modal', () => $('#recordLab').focus());

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
  resetForm();
  bindEvents();
  loadData();
});
