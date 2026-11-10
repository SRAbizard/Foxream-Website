# Foxream — job matching for Indonesian workers in Japan

Laravel 13, Filament 5 (admin panel at `/admin`, candidate dashboard at
`/dashboard`), Livewire 4 and Tailwind 4. The brand name, legal name and
logos are set in `config/brand.php` (`BRAND_*` in `.env`), never in the code.

## Running it on a laptop

Double-click `start-dev.bat`: it serves http://localhost:8001 with SQLite
(`database/database.sqlite`). After changing CSS or JavaScript, run
`npm run build`.

Sample content to try the site with (FAQs, testimonials, companies,
vacancies, a blog post, an event and an album) and one-click demo accounts:

```bash
php artisan db:seed --class=SampleContentSeeder
php artisan demo:setup
```

Never run these two on the live site.

## Deploying

### What the server needs

- PHP 8.3 or newer with the `pdo_sqlite` (or `pdo_mysql`), `mbstring`,
  `intl`, `gd`, `zip`, `fileinfo` and `bcmath` extensions
- Composer, and Node.js 20+ to build the CSS and JavaScript
- The web server's document root pointing at `public/`
- An SMTP mailbox: candidates must confirm their email before they can sign in
- Optional: LibreOffice, for the rirekisho (CV) PDF; without it a simpler
  PDF is made instead

No queue worker or cron job is needed.

### The `.env` file

Copy `.env.example` to `.env` and set at least:

| Setting | Value |
| --- | --- |
| `APP_ENV` | `production` (this also hides the demo accounts and their switcher) |
| `APP_DEBUG` | `false` |
| `APP_URL` | the site's address, e.g. `https://example.com` |
| `DB_*` | the database; SQLite needs nothing else |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | the SMTP mailbox |
| `BOOTSTRAP_ADMIN_NAME`, `BOOTSTRAP_ADMIN_EMAIL`, `BOOTSTRAP_ADMIN_PASSWORD` | the first admin account, created by `db:seed` |
| `BRAND_NAME`, `BRAND_COMPANY`, `BRAND_LEARNING`, `BRAND_MAKER` | the brand; `BRAND_MAKER` is the "Made by" line in the footer |
| `DEEPL_API_KEY`, `GEMINI_API_KEY` | optional: Japanese translation for the CV, and reading job orders |
| `LIBREOFFICE_BIN` | optional: path to `soffice` if it is not in a standard place |

Keep passwords and API keys in the server's `.env` only, never in the code
or in chat.

### First install

```bash
git clone https://github.com/SRAbizard/foxream-website.git
cd foxream-website
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env        # then fill it in, see above
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force # roles, first admin, categories, visa steps; no sample content
php artisan storage:link
php artisan optimize
php artisan filament:optimize
```

The web server must be able to write to `storage/` and `bootstrap/cache/`.

### Every update

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
php artisan up
```

### After the first install

Sign in at `/admin` with the bootstrap admin, change its password, then fill in:

- **Website Settings**: contact details, social media, offices, the numbers
  on the homepage and About page (empty ones are hidden), the page
  pictures and the learning platform's address
- **Legal**: privacy policy, terms and the anti-fraud warnings; have them
  checked against the company's real details
- the content itself: companies, job vacancies, FAQs, testimonials, blog,
  events and gallery

Uploaded files live in `storage/app/public`. To move them from another
install, copy that folder; the database only stores their paths.
