# Global Supply Chain Risk Intelligence Platform

Platform Monitoring Risiko Rantai Pasok Global — multi-API data aggregation, risk scoring, geospatial visualization.

## Tech stack (spec)

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Laravel 12, MySQL |
| Frontend | Bootstrap 5, vanilla JS (ES6 + AJAX), Vite 7 |
| Charts | Chart.js |
| Maps | Leaflet.js + OpenStreetMap |
| Deployment | Docker (optional), GitHub |


## External APIs (all free, no paid keys)

| API | Data | Key needed? |
|---|---|---|
| Open-Meteo | Weather (temp, rain, wind, storm risk) | No |
| World Bank | GDP, inflation, population, exports, imports | No |
| REST Countries | Country info, currency, region, language | No |
| ExchangeRate | Real-time currency exchange rates | Yes (free tier) |
| GNews | Economic, logistics, geopolitic news | Yes (free tier) |
| World Port Index | Port locations, country, coordinates | Dataset (static) |

## Current state

Laravel 12 with Bootstrap 5, Chart.js, and Leaflet.js installed. 8 database tables + seeders created, 5 API endpoints + controllers, 6 service classes, 10 Blade dashboard views with AJAX/JS, admin CRUD, side-effect risk scoring, and sentiment analysis ready.

**Still to build**: Docker setup (optional), GNews/ExchangeRate API keys in `.env` for live data.

## Commands

| Command | What it does |
|---|---|
| `composer run dev` | Starts 4 concurrent processes: `php artisan serve`, `queue:listen`, `pail`, `npm run dev` (Vite HMR) |
| `composer run test` | Runs `php artisan config:clear` then `php artisan test` |
| `composer run setup` | One-shot: `composer install`, copies `.env`, runs `key:generate`, `migrate --force`, `npm install && npm run build` |
| `php artisan test --filter=MethodName` | Run a single test |
| `./vendor/bin/pint` | Lint with Laravel Pint (default config, no custom `pint.json`) |

## Testing

- **PHPUnit 11**, two suites: `tests/Unit` (extend `PHPUnit\Framework\TestCase`) and `tests/Feature` (extend `Tests\TestCase`)
- **Database**: SQLite `:memory:` in tests (set in `phpunit.xml`)
- `DatabaseMigrations` is opt-in (ExampleTest uses it). `RefreshDatabase` has issues with `:memory:` — prefer `DatabaseMigrations`

## Database tables (created in `database/migrations/`)

`countries` · `risk_scores` · `news_cache` · `ports` · `watchlists` · `articles` · `positive_words` · `negative_words`

(
`users` from default Laravel migration)

## REST API (stubbed in `routes/api.php`)

| Endpoint | Purpose |
|---|---|
| `GET /api/countries` | List countries with GDP, inflation, population, currency, weather |
| `GET /api/risk` | Risk score per country (weather + inflation + FX + sentiment) |
| `GET /api/ports` | World port locations (from World Port Index) |
| `GET /api/news` | Filtered logistics/trade/shipping/economy news |
| `GET /api/currency` | Real-time exchange rates |

## Key conventions

- **Route files**: `routes/web.php` (browser), `routes/api.php` (REST), `routes/console.php` (Artisan)
- **Views**: `resources/views/layouts/app.blade.php` (master), 10 page views in subdirectories: `dashboard/`, `weather/`, `currency/`, `news/`, `ports/`, `comparison/`, `watchlist/`, `viz/`, `admin/`
- **Sentiment analysis**: lexicon-based PHP approach (`positive_words` / `negative_words` dictionary tables, count matches, compare scores)
- **Risk score**: weighted model — Weather ~30%, Inflation ~20%, Political News ~40%, Currency ~10% (adjustable)
- `.env` gitignored — copy `.env.example`, run `php artisan key:generate`
- No CI/CD workflows (no `.github/`)
- `public/build/` gitignored (Vite output)