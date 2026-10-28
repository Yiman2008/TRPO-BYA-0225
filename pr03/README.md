# Практическая работа №3 — Bootstrap 5, Bootstrap JavaScript и базовый JavaScript

**Панель статуса лабораторных работ**: navbar с мобильным меню, карточки показателей, журнал действий в адаптивной таблице, modal с формой добавления записи, offcanvas с уведомлениями, accordion-справка и toast. Все данные берутся из `data/dashboard.json` и `data/notifications.json`.

**Технологии:** HTML5, Bootstrap 5.3.3 (CSS + `bootstrap.bundle.min.js` с Popper), чистый JavaScript (ES2020), Fetch API.

## Запуск

Требования: любой современный браузер и Python 3 (или Node.js) — нужен локальный HTTP-сервер, потому что `fetch()` не читает JSON со страницы, открытой как `file://`.

```bash
cd pr03
python -m http.server 8000      # или: npx serve .
```

Открыть <http://localhost:8000>. Bootstrap лежит локально в `vendor/bootstrap`, интернет не нужен.

Если открыть `index.html` двойным кликом, страница покажет красный блок «Не удалось загрузить данные» с подсказкой — это обработанное ошибочное состояние.

## Структура

```
pr03/
├── index.html                  ← разметка: header/nav, main (section#overview, #metrics, #activity, #upcoming), footer,
│                                 offcanvas, modal, toast
├── css/custom.css              ← небольшие собственные стили
├── js/app.js                   ← собственный JS: загрузка dataset, отрисовка, обработчики событий
├── data/
│   ├── dashboard.json          ← проект, пользователь, metrics, statuses, activity, upcoming
│   └── notifications.json      ← уведомления: id, title, message, type, time, read
├── images/
│   ├── avatar.svg              ← аватар в navbar (dashboard.user.avatar)
│   └── chart.svg               ← диаграмма в блоке «Обзор»
└── vendor/bootstrap/           ← bootstrap.min.css и bootstrap.bundle.min.js 5.3.3
```

## Маршрут данных

`fetch('data/dashboard.json')` + `fetch('data/notifications.json')` → `state` в `app.js` → функции `render*()` создают DOM-элементы:

| Поле dataset | Где отображается |
|---|---|
| `dashboard.project`, `course`, `semester`, `updatedAt` | заголовок и подзаголовок блока «Обзор» |
| `dashboard.user` | имя и аватар в navbar |
| `dashboard.metrics[]` (5 шт.) | карточки показателей `#metric-{id}`, цвет рамки и числа — из `color`; `labs-done/labs-total` → progress bar |
| `dashboard.statuses` | бейджи статусов, фильтр журнала, select в форме, accordion «Справка по статусам» |
| `dashboard.activity[]` | строки таблицы «Журнал действий», `data-id` = `id` |
| `dashboard.upcoming[]` | список «Ближайшие сроки» и варианты работ в форме |
| `notifications[]` | список в offcanvas, счётчик непрочитанных, первый непрочитанный — в toast при загрузке |

## Используемые Bootstrap-компоненты

Navbar + Collapse, Grid (`container`, `row g-3`, `col-12 col-sm-6 col-xl` / `col-lg-8` / `col-md-6`), Card, Badge, Progress, Table + `.table-responsive`, Forms (`form-control`, `form-select`, `form-label`, `form-text`, `invalid-feedback`, `valid-feedback`), Modal, Offcanvas, Accordion, List group, Toast, Alert, Buttons, Close button, utility-классы (`d-flex`, `gap-*`, `mb-*`, `shadow-sm`, `text-body-secondary` и т.д.).

## Что делает Bootstrap JavaScript, а что — app.js

**Bootstrap JS (`bootstrap.bundle.min.js`)** — поведение готовых компонентов. Он сам находит элементы с `data-bs-toggle` и:
- раскрывает/сворачивает меню navbar (`data-bs-toggle="collapse"`);
- открывает modal (`data-bs-toggle="modal"`), offcanvas (`data-bs-toggle="offcanvas"`), разворачивает accordion;
- закрывает их по `data-bs-dismiss`, Esc, клику по фону; управляет фокусом и `aria-*`;
- анимирует и скрывает toast по таймеру;
- генерирует события `shown.bs.modal`, `hidden.bs.modal`.

**Собственный `js/app.js`** — логика приложения:
1. Загружает оба JSON и показывает ошибку, если загрузка не удалась (или если `bootstrap is not defined`).
2. Строит из данных карточки, таблицу, список сроков, accordion, уведомления, варианты select.
3. Обработчики событий:
   - `submit` формы — валидация, добавление записи в начало журнала, обновление счётчика «На проверке»/«Принято» и progress bar, закрытие modal, toast;
   - `change` фильтра — фильтрует таблицу по статусу, показывает «Нет записей…» при пустом результате;
   - `click` «Отметить все прочитанными» — переключает состояние и текст кнопки, обновляет счётчик в navbar;
   - `input` поля email — живая проверка;
   - `hidden.bs.modal` — сброс формы, `shown.bs.modal` — фокус на первое поле;
   - `click` по пункту меню — закрывает мобильное меню.
4. **Программный вызов Bootstrap:** `bootstrap.Toast.getOrCreateInstance(...).show()`, `bootstrap.Modal.getOrCreateInstance(...).hide()`, `bootstrap.Collapse.getOrCreateInstance(...).hide()`.
5. **Клиентская валидация email:** пустое значение → «Введите email преподавателя», неверный формат (`wrong@`) → «Неверный формат email»; при ошибке ставится `is-invalid`, при успехе — `is-valid`. Остальные поля проверяются через `checkValidity()` (`required`, `minlength`).

## Что изменилось после шага 5

Таблица «Журнал действий» больше не статичная: строки создаются из `dashboard.activity`, статус превращается в цветной бейдж по `dashboard.statuses`, таблица обёрнута в `.table-responsive` — на 375 px она прокручивается внутри карточки, а страница остаётся без горизонтального скролла.

## Собственный CSS (`css/custom.css`)

Только детали, которых нет в utility-классах: `scroll-margin-top` для якорей под sticky-navbar, размер аватара, крупное адаптивное число показателя (`clamp`), толщина цветной рамки карточки, подсветка новой строки, точка в toast, отметка непрочитанного уведомления, мелкий шрифт таблицы на ширине < 768 px. Базовые компоненты Bootstrap не переписываются.

## Сценарий проверки

1. `python -m http.server 8000` → <http://localhost:8000>. Справа снизу появляется toast с непрочитанным уведомлением «Скоро дедлайн ПР-04» из `notifications.json`.
2. 5 карточек показателей, 7 строк журнала, на кнопке «Уведомления» счётчик 3.
3. «Уведомления» → offcanvas со списком → «Отметить все прочитанными» — счётчик скрывается, текст кнопки меняется.
4. Фильтр «Принята» → 3 строки, «Показано записей: 3 из 7».
5. «+ Добавить запись» → «Сохранить» с пустыми полями — поля подсвечены `is-invalid`. Ввести email `wrong@` — «Неверный формат email». Заполнить корректно (`teacher@example.com`) → modal закрывается, в журнале новая подсвеченная строка, toast «Запись добавлена», счётчик «На проверке» +1.
6. DevTools → Responsive: на 375 px меню свёрнуто в кнопку-«бургер», открывается и закрывается после выбора пункта; modal, offcanvas и toast помещаются в экран. На 1440 px карточки показателей в одну строку.
7. Console — без ошибок (`bootstrap is not defined`, дубли id и ошибки обработчиков отсутствуют).

Проверено в Chromium на 375 px и 1440 px: `scrollWidth` равен ширине окна, ошибок в Console нет.
