# ТРПО — практические работы

| Работа | Папка | Запуск |
|---|---|---|
| ПР-01. Семантическая вёрстка | корень репозитория (`index.html`) | открыть `index.html` |
| ПР-02. CSS, адаптивная вёрстка и SCSS | [`pr02/`](pr02/README.md) | открыть `pr02/index.html`; сборка SCSS — `npm install && npm run build` |
| ПР-03. Bootstrap 5 и JavaScript | [`pr03/`](pr03/README.md) | `cd pr03 && python -m http.server 8000` → http://localhost:8000 |
| ПР-04. PHP: формы, GET и POST | [`pr04/`](pr04/README.md) | `cd pr04 && php -S localhost:8000 -t public` |
| ПР-05. PHP ООП: классы и модели | [`pr05/`](pr05/README.md) | `cd pr05 && php -S localhost:8000 -t public` |
| ПР-06. PHP: JSON, сессии, шаблоны | [`pr06/`](pr06/README.md) | `cd pr06 && php -S localhost:8000 -t public` |

## Структура проекта
```
TRPO-BYA-0225/
├── index.html            ← главная (и единственная) страница сайта
├── css/
│   └── styles.css        ← все стили: цвета, отступы, карточки, таблица, форма
├── images/
│   ├── logo.svg          ← логотип в шапке
│   └── cover.svg         ← картинка-обложка на главном экране
├── data/
│   ├── content.csv       ← список учебных тем (таблица, разделитель «;»)
│   └── navigation.json   ← пункты меню в формате JSON
├── .gitignore            ← файлы, которые Git не должен отслеживать
└── README.md             ← этот файл
```
