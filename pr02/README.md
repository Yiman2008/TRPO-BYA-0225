# Практическая работа №2 — CSS, адаптивная вёрстка и SCSS

Адаптивный **каталог учебных курсов**: шапка с навигацией, панель фильтров по направлениям, сетка из 14 карточек, информационный блок и подвал. Стили написаны на SCSS (переменные, partial-файлы, mixin, функции) и скомпилированы в `css/styles.css`.

**Технологии:** HTML5, CSS Grid, Flexbox, SCSS (Dart Sass), немного JavaScript для фильтра.

## Запуск

Готовый CSS уже лежит в репозитории, поэтому для просмотра достаточно открыть `index.html` в браузере (двойной клик или Live Server).

### Сборка SCSS

Требования: Node.js 18+ и npm.

```bash
cd pr02
npm install          # ставит sass в devDependencies
npm run build        # разовая сборка: scss/styles.scss → css/styles.css
npm run watch        # режим наблюдения: пересборка при каждом сохранении .scss
```

Без `npm install` можно так же: `npx sass scss/styles.scss css/styles.css --no-source-map` (разово) и `npx sass --watch scss/styles.scss:css/styles.css` (наблюдение).

## Структура

```
pr02/
├── index.html              ← разметка: header, aside.filters, section.catalog, section.info, footer
├── data/
│   ├── catalog.csv         ← 14 курсов: id;title;category;level;hours;price;image;description
│   └── theme.json          ← цвета, радиус, отступы, ширина контейнера, брейкпоинты
├── images/                 ← product-01..03.svg — обложки карточек (поле image в catalog.csv)
├── scss/
│   ├── styles.scss         ← главный файл, только @use остальных частей
│   ├── _variables.scss     ← все значения из theme.json
│   ├── _functions.scss     ← rem(), tint()
│   ├── _mixins.scss        ← container, focus-ring, surface, below
│   ├── _base.scss          ← сброс, body, типографика, CSS custom properties
│   ├── _layout.scss        ← шапка, сетка страницы, сетка карточек, подвал, брейкпоинты
│   └── components/
│       ├── _card.scss      ← карточка курса
│       ├── _badge.scss     ← бейдж направления (генерируется циклом @each)
│       ├── _filters.scss   ← панель фильтров и chip-кнопки
│       └── _info.scss      ← информационный блок
├── css/styles.css          ← результат компиляции (подключён в index.html)
├── js/filter.js            ← фильтр карточек по направлению
└── package.json            ← скрипты build / watch
```

## Как используется dataset

| Файл | Где применяется |
|------|-----------------|
| `catalog.csv` | Каждая строка → `<article class="card" id="course-{id}">`. `title` → заголовок, `category` → badge и `data-category` для фильтра, `level` и `hours` → `<dl class="card__meta">`, `price` → цена, `image` → обложка, `description` → текст. Данные перенесены в HTML без посторонних текстов. |
| `theme.json` | `colors` → `$color-*`, `radius` → `$radius`, `spacing` → `$space-xs..xl`, `containerWidth` → `$container-width`, `breakpoints` → `$breakpoint-sm/md`, `fontFamily` → `$font-family`. Все значения заданы один раз в `_variables.scss`. |

## Этапы и что менялось

1. **Структура и dataset** — папки `scss`, `css`, `images`, `data`, файлы dataset.
2. **Рабочий CSS без препроцессора** — разметка каталога, Grid для `.layout` (240px + 1fr) и `.catalog` (`repeat(auto-fill, minmax(220px, 1fr))`), один медиазапрос.
3. **Перенос в SCSS** — повторяющиеся цвета/отступы/радиусы вынесены в переменные, стили разделены на variables, base, layout и components.
4. **Шаг 8 (вложенность).** Используется BEM-вложенность `&__title`, `&--active`, `&:hover`; максимальная глубина — 3 уровня (`.card` → `&__meta` → `dt`). Вместо длинных цепочек `.card .card__body .card__meta dt` получились короткие читаемые блоки.
5. **Шаг 9 (mixin).** `@include surface` заменил повторявшийся набор «белый фон + рамка + радиус + padding» в `.card`, `.filters`, `.info`, `.catalog-section__empty`; `@include focus-ring` даёт одинаковый фокус ссылкам и chip-кнопкам; `@include container` — адаптивный контейнер; `@include below($bp)` — медиазапросы.
6. **Шаг 10 (функции).** `rem($px)` пересчитывает размеры шрифтов через `math.div`, `tint($color)` делает полупрозрачный фон для badge и активного chip. Badge-модификаторы генерируются `@each` по карте `$category-colors`, а не пишутся вручную 4 раза.
7. **Шаг 12 (адаптивная сетка).** `auto-fill + minmax` даёт 3 колонки на 1440px, 2 — на 768px, 1 — на 375px.
8. **Шаг 13 (два breakpoint).**
   - `768px` — перестраивается основная раскладка: панель фильтров переносится над карточками, список фильтров становится горизонтальной лентой.
   - `480px` — меняется плотность: каталог строго в одну колонку с меньшим gap, компактный padding карточки, навигация растягивается на всю ширину.

## Проверка (DevTools → Responsive)

| Ширина | Раскладка | Горизонтальный скролл |
|-------:|-----------|----------------------|
| 375 px | фильтры сверху, 1 колонка | нет (`scrollWidth = 375`) |
| 768 px | фильтры сверху, 2 колонки | нет (`scrollWidth = 768`) |
| 1440 px | фильтры слева, 3 колонки | нет (`scrollWidth = 1440`) |

Сценарий проверки: открыть `index.html` → нажать «Базы данных» — остаётся 3 карточки и счётчик «3» → «Все» — снова 14. Console без ошибок. Ошибочное/пустое состояние: если в категории нет карточек, показывается блок «В этой категории пока нет курсов».

Чтобы убедиться, что SCSS-переменная действительно управляет оформлением: поменять `$radius: 12px` на `$radius: 0` в `_variables.scss`, выполнить `npm run build` — скругления исчезнут сразу у карточек, фильтров, chip-кнопок и инфоблока.

## SCSS-переменная и CSS custom property

- **SCSS-переменная** (`$color-primary`) существует только при компиляции. В итоговом `styles.css` её нет — на её место подставлено значение `#315efb`. Изменить её в браузере нельзя, нужна пересборка. Удобна для расчётов (`rem()`, `tint()`), циклов и медиазапросов.
- **CSS custom property** (`--color-primary`) попадает в CSS и живёт в браузере: наследуется по DOM, видна и меняется в DevTools, может переопределяться в другом селекторе или через JavaScript во время работы страницы. В проекте объявлена в `_base.scss` (`:root { --color-primary: #{$color-primary}; }`) и используется для ссылок и акцентной полосы в инфоблоке.

## Зачем partial-файлы

Файлы с `_` в начале имени (`_variables.scss`, `_card.scss` …) не компилируются сами по себе в отдельный CSS — они подключаются через `@use` в `styles.scss`. Это позволяет разделить стили по смыслу (настройки, база, раскладка, компоненты), быстро находить нужный кусок и получать на выходе один файл `css/styles.css`.

## Где применён mixin

- `surface` — `.card`, `.filters`, `.info`, `.catalog-section__empty`;
- `focus-ring` — `.site-header__logo`, `.site-nav__link`, `.chip`;
- `container` — `.container`;
- `below($breakpoint)` — все медиазапросы в `_layout.scss`, `_card.scss`, `_filters.scss`.
