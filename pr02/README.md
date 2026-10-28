# Практическая работа №2 — CSS, адаптивная вёрстка и SCSS

Адаптивный **учебный каталог**: шапка с навигацией, панель фильтров по категориям, сетка из 18 карточек, информационный блок и подвал. Стили написаны на SCSS (переменные, partial-файлы, mixin, функции) и скомпилированы в `css/styles.css`.

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
│   ├── catalog.csv         ← 18 позиций: id;name;category;price;rating
│   └── theme.json          ← accent, surface, background, text, radius, space
├── images/                 ← product-01..03.svg из dataset/assets — обложки карточек по категории
├── scss/
│   ├── styles.scss         ← главный файл, только @use остальных частей
│   ├── _variables.scss     ← все значения из theme.json
│   ├── _functions.scss     ← rem(), tint()
│   ├── _mixins.scss        ← container, focus-ring, surface, below, button-variant
│   ├── _base.scss          ← сброс, body, типографика, CSS custom properties
│   ├── _layout.scss        ← шапка, сетка страницы, сетка карточек, подвал, брейкпоинты
│   └── components/
│       ├── _card.scss      ← карточка товара
│       ├── _badge.scss     ← бейдж категории (генерируется циклом @each)
│       ├── _button.scss    ← локальный компонент-кнопка
│       ├── _filters.scss   ← панель фильтров и chip-кнопки
│       └── _info.scss      ← информационный блок
├── css/styles.css          ← результат компиляции (подключён в index.html)
├── js/filter.js            ← фильтр карточек по категории
└── package.json            ← скрипты build / watch
```

## Как используется dataset

| Файл | Где применяется |
|------|-----------------|
| `catalog.csv` | Каждая строка → `<article class="card" id="item-{id}">`. `name` → заголовок, `category` → badge, `data-category` для фильтра и обложка (Книги → product-01, Курсы → product-02, Инструменты → product-03), `price` → цена, `rating` → звёзды и число. Все 18 строк перенесены в HTML без посторонних текстов. |
| `theme.json` | `accent` → `$accent`, `surface` → `$surface`, `background` → `$background`, `text` → `$text`, `radius: 14` → `$radius: 14px`, `space: 16` → `$space: 16px`. Все значения заданы один раз в `_variables.scss`; остальные отступы (`$space-xs … $space-xl`) и цвета (`$muted`, `$border`, `$accent-dark`) **вычисляются** из них. |

## Этапы и что менялось

1. **Структура и dataset** — папки `scss`, `css`, `images`, `data`, файлы dataset.
2. **Рабочий CSS без препроцессора** — разметка каталога, Grid для `.layout` (240px + 1fr) и `.catalog` (`repeat(auto-fill, minmax(220px, 1fr))`), один медиазапрос.
3. **Перенос в SCSS** — повторяющиеся цвета/отступы/радиусы вынесены в переменные, стили разделены на variables, base, layout и components.
4. **Шаг 8 (вложенность).** Используется BEM-вложенность `&__title`, `&--active`, `&:hover`; максимальная глубина — 3 уровня (`.card` → `&__meta` → `dt`). Вместо длинных цепочек `.card .card__body .card__meta dt` получились короткие читаемые блоки.
5. **Шаг 9 (mixin).** `@include surface` заменил повторявшийся набор «белый фон + рамка + радиус + padding» в `.card`, `.filters`, `.info`, `.catalog-section__empty`; `@include button-variant($accent)` собирает кнопку «Подробнее» (фон, hover, focus); `@include focus-ring` даёт одинаковый фокус ссылкам и chip-кнопкам; `@include container` — адаптивный контейнер; `@include below($bp)` — медиазапросы.
6. **Шаг 10 (функции).** `rem($px)` пересчитывает размеры шрифтов через `math.div`, `tint($color)` делает полупрозрачный фон для badge и активного chip. Отступы считаются от `$space` (`$space * 0.5`, `$space * 1.5`), цвета `$muted`/`$border` — через `color.mix`. Badge-модификаторы генерируются `@each` по карте `$category-colors`, а не пишутся вручную 3 раза.
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

Сценарий проверки: открыть `index.html` → нажать «Курсы» — остаётся 6 карточек и счётчик «6» → «Все» — снова 18. Console без ошибок. Ошибочное/пустое состояние: если в категории нет карточек, показывается блок «В этой категории пока нет товаров».

Чтобы убедиться, что SCSS-переменная действительно управляет оформлением: поменять `$radius: 14px` на `$radius: 0` в `_variables.scss`, выполнить `npm run build` — скругления исчезнут сразу у карточек, кнопок, фильтров, chip-кнопок и инфоблока. Аналогично смена `$space: 16px` пропорционально меняет все отступы.

## SCSS-переменная и CSS custom property

- **SCSS-переменная** (`$accent`) существует только при компиляции. В итоговом `styles.css` её нет — на её место подставлено значение `#315efb`. Изменить её в браузере нельзя, нужна пересборка. Удобна для расчётов (`rem()`, `tint()`), циклов и медиазапросов.
- **CSS custom property** (`--color-accent`) попадает в CSS и живёт в браузере: наследуется по DOM, видна и меняется в DevTools, может переопределяться в другом селекторе или через JavaScript во время работы страницы. В проекте объявлена в `_base.scss` (`:root { --color-accent: #{$accent}; }`) и используется для ссылок и акцентной полосы в инфоблоке.

## Зачем partial-файлы

Файлы с `_` в начале имени (`_variables.scss`, `_card.scss` …) не компилируются сами по себе в отдельный CSS — они подключаются через `@use` в `styles.scss`. Это позволяет разделить стили по смыслу (настройки, база, раскладка, компоненты), быстро находить нужный кусок и получать на выходе один файл `css/styles.css`.

## Где применён mixin

- `surface` — `.card`, `.filters`, `.info`, `.catalog-section__empty`;
- `focus-ring` — `.site-header__logo`, `.site-nav__link`, `.chip`, `.button`;
- `button-variant` — `.button`;
- `container` — `.container`;
- `below($breakpoint)` — все медиазапросы в `_layout.scss`, `_card.scss`, `_filters.scss`.
