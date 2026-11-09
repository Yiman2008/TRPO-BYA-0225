# Практические работы №7–11

Интернет-магазин учебных товаров на PHP MVC. Реализованы главная, каталог из JSON, карточка товара, фильтры, сортировка, корзина в сессии и оформление заказа с серверной валидацией. Работы последовательно развивают один проект в `shop`; работы №1–6 находятся в прежних папках репозитория.

## Запуск с нуля

Требования: PHP 8.1+ с обычными расширениями JSON и session, Git. Composer, npm, база данных и сторонний PHP-фреймворк не нужны. PHP должен быть доступен в терминале (`php -v`).

```bash
git clone https://github.com/Yiman2008/TRPO-BYA-0225.git
cd TRPO-BYA-0225/shop
php -S 127.0.0.1:8000 -t public public/router.php
```

Открыть http://127.0.0.1:8000. Остановить сервер: Ctrl+C. Документ-корень — только `public`, а `public/router.php` передаёт адреса приложению и пропускает CSS/SVG. Запускать сервер из корня всего репозитория нельзя: там находятся другие практические и закрытые данные.

Папка `var` должна быть доступна PHP на запись. Первый заказ создаёт `var/orders.json` из исходного `dataset/pr10/orders_seed.json`. Изменяемые заказы и lock-файлы не попадают в Git. Для чистого начала в учебной среде после остановки сервера можно удалить созданный `var/orders.json`; новая приватная вкладка создаёт отдельную корзину. Никакие реальные платежи или письма приложение не отправляет.

## Этапы и история

| Работа | Результат | Подробности | Тег |
|---|---|---|---|
| №7 | Единая точка входа, Router, контроллеры и общий layout | [Описание](docs/pr07.md) | `pr07-complete` |
| №8 | Модели Product/Category, Repository, фильтр и сортировка | [Описание](docs/pr08.md) | `pr08-complete` |
| №9 | CartService, сессия, POST, flash и расчёт сумм | [Описание](docs/pr09.md) | `pr09-complete` |
| №10 | Валидация, доставка, JSON-заказы и подтверждение | [Описание](docs/pr10.md) | `pr10-complete` |
| №11 | Проверка архитектуры, типизированный ввод, безопасность и регрессия | [Описание](docs/pr11.md) | `pr11-complete`, `shop-v1.0.0` |

На каждом этапе выполнено минимум пять содержательных коммитов. Перед рефакторингом создан `pre-pr11-refactor`. Для просмотра истории: `git log --oneline -- shop`. Для просмотра старого этапа: `git show pr08-complete:shop/src/Controller/CatalogController.php`. Чтобы запустить старую версию, используйте чистую рабочую копию и `git switch --detach pr08-complete`.

## Архитектура MVC

```text
HTTP → public/index.php → bootstrap.php → Core/Application
                                       ├─ Session (HTTP-состояние)
                                       ├─ ProductRepository → Product + Category
                                       ├─ CartService ← массив id => количество
                                       └─ OrderValidator + OrderRepository → CheckoutService
          Application → Router → Controller → View → шаблон → layout → HTML
```

`Application` собирает зависимости через конструкторы. `Router` сопоставляет путь и метод, без формирования HTML. `HomeController` получает данные страницы; `CatalogController` получает ProductRepository; `CartController` получает CartService и Session; `CheckoutController` получает валидатор и CheckoutService. Модели не читают HTTP-глобальные переменные. Репозитории работают с файлами и возвращают данные/модели, представления только отображают подготовленное содержимое. Автозагрузка соответствует `App\` → `src/`.

```text
shop/
├── bootstrap.php             автозагрузка и функции e/money
├── public/                   index.php, router.php, CSS и SVG
├── src/Core/                 Application, Router, View, Request, Session, Json
├── src/Controller/           Home, Catalog, Cart, Checkout, Error
├── src/Model/                Product, Category, OrderData
├── src/Repository/           ProductRepository, OrderRepository
├── src/Service/              CartService, OrderValidator, CheckoutService
├── views/                    HTML-шаблоны и общий layout
├── dataset/pr07..pr11/       исходные данные без изменений
├── tests/                    проверки данных, архитектуры, HTTP и синтаксиса
├── docs/                     результаты и описание этапов
└── var/                      локальное состояние, кроме .gitkeep исключено из Git
```

## Маршруты

| Метод | Путь | Действие | Ожидаемый статус |
|---|---|---|---|
| GET | `/` | HomeController::index → home | 200 |
| GET | `/about` | HomeController::about → about | 200 |
| GET | `/catalog?category=1&sort=price` | CatalogController::index → catalog | 200 |
| GET | `/product?id=1` | CatalogController::show → product | 200; неизвестный id → 404 |
| GET | `/cart` | CartController::index → cart | 200 |
| POST | `/cart/add` | CartController::add | 302 на /cart |
| POST | `/cart/update` | CartController::update | 302 на /cart |
| POST | `/cart/remove` | CartController::remove | 302 на /cart |
| GET | `/checkout` | CheckoutController::index → checkout | 200; пустая корзина → 302 |
| POST | `/checkout` | CheckoutController::store | 302; ошибки → 422 |
| GET | `/order/success` | CheckoutController::success → success | 200; чужая/новая сессия → 404 |
| GET | `/missing`, `/unknown` | ErrorController → error | 404 |

POST требует корректный CSRF-токен. Неизвестный путь возвращает 404; известный путь с неподдерживаемым методом — 405 с Allow. Некорректный тип id/количества/фильтра — 422, неизвестный товар — 404, неверный CSRF — 403. Неожиданная ошибка возвращает 500 и записывается в журнал сервера без выдачи технических подробностей в HTML.

## Использование dataset

| Исходный файл | Где используется |
|---|---|
| pr07/routes.csv | Application читает таблицу и регистрирует обязательные маршруты |
| pr07/pages.json | HomeController и общая шапка |
| pr07/assets/shop-logo.svg | Копия в public/shop-logo.svg, логотип layout |
| pr08/products.json, categories.json | ProductRepository, основной каталог из 24 товаров |
| pr08/catalog_cases.csv | tests/run.php: весь каталог, книги, неизвестная категория |
| pr09/products.json | Отдельный ProductRepository для воспроизводимых тестов корзины |
| pr09/cart_scenarios.json | tests/run.php: add, remove, set и итоговые суммы |
| pr10/delivery_methods.json | OrderValidator и варианты формы checkout |
| pr10/orders_seed.json | Начальное состояние OrderRepository |
| pr10/order_cases.json | tests/run.php и tests/http.php: пустая форма, неверный email, успешный заказ |
| pr11/architecture_checklist.csv | tests/architecture.php и таблица ниже |
| pr11/route_tests.csv | tests/http.php: метод, адрес и HTTP-статус |
| pr11/security_cases.json | tests/http.php: HTML, отрицательное количество, неизвестный id |

Наборы №8 и №9 намеренно сохранены раздельно: у одного id разные названия/цены. Основное приложение продолжает ассортимент №8; сценарии №9 проверяют тот же CartService на исходных ценах №9. Итоги №9: 4300, 0 и 12900 рублей. Сессия содержит только количества, flash, CSRF и краткое подтверждение последнего заказа. Цены берутся с сервера; сумма считается в `CartService::summary`, доставка добавляется в `CheckoutService`.

## Чек-лист архитектуры

| Пункт architecture_checklist.csv | Реализация | Результат |
|---|---|---|
| Single entry point | public/index.php запускает Application | Пройдено |
| Router without HTML | src/Core/Router.php выбирает callable | Пройдено |
| Controllers choose views | src/Controller/* вызывает View::render | Пройдено |
| Models without HTTP globals | src/Model/* содержит предметные данные | Пройдено |
| Views without data access | views/* не открывает JSON/CSV | Пройдено |
| Namespaces | Все классы App\Core/Controller/Model/Repository/Service | Пройдено |
| Autoload | bootstrap.php, spl_autoload_register | Пройдено |
| 404 handler | HttpException + ErrorController + views/error.php | Пройдено |

## Проверка

Из папки `shop`, в другом терминале:

```bash
php tests/lint.php
php tests/run.php
php tests/architecture.php
php tests/http.php
```

HTTP-тест сам запускает сервер на свободном порту и останавливает его. Тестовые заказы и сессии создаются во временном каталоге; рабочие заказы не изменяются. Для тестов нужны разрешённые `proc_open` и локальные TCP-соединения. Переменная `SHOP_ORDERS_PATH` используется тестами для изоляции хранилища; обычному запуску она не нужна.

Локальная проверка на PHP 8.4.27: 37 PHP-файлов прошли lint, 23 проверки моделей/сервисов, 8 проверок архитектуры и 39 HTTP-проверок прошли. Подробности — [результаты](docs/verification.md). GitHub Actions повторяет проверки на PHP 8.1 и 8.4.

Сценарий демонстрации: открыть каталог → выбрать категорию и сортировку → открыть карточку → добавить товар → изменить количество → оформить самовывоз или курьера → получить номер заказа. Затем проверить `/unknown`, неизвестный товар, пустую форму, неверный email и адрес курьера. Новая приватная вкладка показывает пустую корзину и не видит подтверждение другой сессии.

## Ограничения проекта

Это учебный проект без регистрации, оплаты, отправки уведомлений и административного интерфейса. JSON с файловой блокировкой рассчитан на один сервер и небольшой объём; для роста нужны база данных, транзакции и миграции. Stock проверяется при выборе количества, но не резервируется и не списывается при заказе. Для конкурентных продаж необходим атомарный учёт остатков.

Встроенный сервер PHP предназначен для локального запуска. Production потребует HTTPS, подходящий веб-сервер, управление конфигурацией, мониторинг, ограничения частоты запросов, политику хранения контактных данных и резервное копирование. Контактные данные локальных учебных заказов сохраняются в открытом JSON вне public; используйте вымышленные данные. CSRF и экранирование реализованы, но не заменяют полный аудит безопасности. GitHub Pages не выполняет PHP: для демонстрации нужен локальный PHP либо PHP-хостинг.
