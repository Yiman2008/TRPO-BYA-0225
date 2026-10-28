# Практическая работа №3 — Bootstrap 5, Bootstrap JavaScript и базовый JavaScript

**Административная панель**: navbar с мобильным меню, карточки показателей, таблица последних действий, modal с формой добавления записи, offcanvas с уведомлениями, accordion-справка и toast. Все данные берутся из `data/dashboard.json` и `data/notifications.json`.

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
├── index.html                  ← разметка: header/nav, main (section#overview, #metrics, #activity, #help), footer,
│                                 offcanvas, modal, toast
├── css/custom.css              ← небольшие собственные стили
├── js/app.js                   ← собственный JS: загрузка dataset, отрисовка, обработчики событий
├── data/
│   ├── dashboard.json          ← metrics[] (title, value) и rows[] (id, action, status)
│   └── notifications.json      ← уведомления: type, text
├── images/
│   ├── avatar.svg              ← аватар в navbar (dataset/assets)
│   └── chart.svg               ← иллюстрация в блоке «Обзор» (dataset/assets)
└── vendor/bootstrap/           ← bootstrap.min.css и bootstrap.bundle.min.js 5.3.3
```

## Маршрут данных

`fetch('data/dashboard.json')` + `fetch('data/notifications.json')` → `state` в `app.js` → функции `render*()` создают DOM-элементы:

| Поле dataset | Где отображается |
|---|---|
| `dashboard.metrics[]` (4 шт.: Пользователи, Заявки, Ошибки, Готовность) | карточки показателей `#metric-1…4`; значение «Готовность» (`"92%"`) переводится в число для progress bar в «Обзоре»; «Заявки» увеличивается при добавлении записи |
| `dashboard.rows[]` (8 шт.) | строки таблицы «Последние действия»: `id` → `data-id` и колонка ID, `action` → текст, `status` → цветной badge |
| `rows[].status` (Новая / В работе / Готово) | варианты фильтра, select в форме, accordion «Справка по статусам» с количеством записей |
| `notifications[]` | список в offcanvas и счётчик непрочитанных; текст `success` («Изменения сохранены») показывается в toast после сохранения, текст `warning` («Проверьте обязательные поля») — при ошибке валидации |

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
   - `submit` формы — валидация, добавление строки в начало таблицы, +1 к показателю «Заявки», закрытие modal, toast с текстом из notifications.json (или warning-toast при ошибке);
   - `change` фильтра — фильтрует таблицу по статусу, показывает «Нет записей…» при пустом результате;
   - `click` «Отметить все прочитанными» — переключает состояние и текст кнопки, обновляет счётчик в navbar;
   - `input` поля email — живая проверка;
   - `hidden.bs.modal` — сброс формы, `shown.bs.modal` — фокус на первое поле;
   - `click` по пункту меню — закрывает мобильное меню.
4. **Программный вызов Bootstrap:** `bootstrap.Toast.getOrCreateInstance(...).show()`, `bootstrap.Modal.getOrCreateInstance(...).hide()`, `bootstrap.Collapse.getOrCreateInstance(...).hide()`.
5. **Клиентская валидация email:** пустое значение → «Введите email ответственного», неверный формат (`wrong@`) → «Неверный формат email»; при ошибке ставится `is-invalid`, при успехе — `is-valid`. Остальные поля проверяются через `checkValidity()` (`required`, `minlength`).

## Что изменилось после шага 5

Таблица «Последние действия» больше не статичная: строки создаются из `dashboard.rows`, статус превращается в цветной бейдж (Новая — серый, В работе — жёлтый, Готово — зелёный), таблица обёрнута в `.table-responsive` — на 375 px она прокручивается внутри карточки, а страница остаётся без горизонтального скролла.

## Собственный CSS (`css/custom.css`)

Только детали, которых нет в utility-классах: `scroll-margin-top` для якорей под sticky-navbar, размер аватара, крупное адаптивное число показателя (`clamp`), толщина цветной рамки карточки, подсветка новой строки, точка в toast, отметка непрочитанного уведомления, мелкий шрифт таблицы на ширине < 768 px. Базовые компоненты Bootstrap не переписываются.

## Сценарий проверки

1. `python -m http.server 8000` → <http://localhost:8000>.
2. 4 карточки показателей (128 / 43 / 3 / 92%), progress bar 92%, 8 строк в таблице, на кнопке «Уведомления» счётчик 2.
3. «Уведомления» → offcanvas с двумя уведомлениями → «Отметить все прочитанными» — счётчик скрывается, текст кнопки меняется.
4. Фильтр «Готово» → 3 строки, «Показано записей: 3 из 8».
5. «+ Добавить запись» → «Сохранить» с пустыми полями — поля подсвечены `is-invalid`, toast «Проверьте обязательные поля». Ввести email `wrong@` — «Неверный формат email». Заполнить корректно (`admin@example.test`) → modal закрывается, в таблице новая подсвеченная строка #9, показатель «Заявки» 43 → 44, toast «Изменения сохранены…».
6. Ввести в «Действие» `<b>test</b>` — в таблице отображается как текст, а не как тег.
7. DevTools → Responsive: на 375 px меню свёрнуто в бургер, открывается и закрывается после выбора пункта; modal, offcanvas и toast помещаются в экран. На 1440 px 4 карточки в одну строку.
8. Console — без ошибок (`bootstrap is not defined`, дубли id и ошибки обработчиков отсутствуют).

Проверено в Chromium на 375 px и 1440 px: `scrollWidth` равен ширине окна, ошибок в Console нет.
