# Crowdmap

A collaborative mindmapping application built with PHP and Angular: a group builds a mindmap
of statements together and votes on them.

Crowdmap is open source and self-hostable: it runs on PHP with SQLite out of the box, so a
single small server is enough.

## Run it yourself

```bash
cp .env.example .env
docker compose up --build
```

The PHP container creates the schema on start. Then open http://localhost:8080. Without Docker, follow Installation below.

![Tests](https://github.com/marijnvandevoorde/crowdmap/actions/workflows/tests.yml/badge.svg)
[![codecov](https://codecov.io/gh/marijnvandevoorde/crowdmap/branch/main/graph/badge.svg)](https://codecov.io/gh/marijnvandevoorde/crowdmap)

## Features

- Create interactive mindmaps with hierarchical statements
- Collaborative voting on statements
- Real-time updates
- Statement merging for moderators
- Duplicate statement detection with automatic vote aggregation

## Requirements

- PHP 8.1 or higher
- SQLite (local development) or MySQL (production: MySQL HeatWave)
- Composer
- Node.js and npm (for frontend)

## Installation

### Backend

```bash
composer install
php bin/migrate
```

### Frontend

```bash
cd frontend
npm install
npm start
```

### Database

SQLite is the default. Setting `MYSQL_HOST` (with `MYSQL_PORT`, `MYSQL_DATABASE`,
`MYSQL_USER`, `MYSQL_PASSWORD`) switches to MySQL, over TLS unless
`MYSQL_SSL_MODE=DISABLED`; see `.env.example`. `php bin/migrate` creates the schema on
either. `php bin/import-sqlite <file>` copies a SQLite database into an empty MySQL one,
keeping ids.

### Analytics and consent

`frontend/public/consent.js`, loaded first in `index.html`, sets Google Consent Mode v2
defaults to denied, loads Google Tag Manager (`GTM_ID` at the top of that file; empty means
nothing loads) and shows a cookie banner. Accept grants `analytics_storage` only; the GA4 tag
is configured in the GTM container. The privacy page is the `/privacy` route.

## Running Tests

```bash
vendor/bin/phpunit
```

The integration tests run against MySQL with `TEST_DB=mysql` and the `MYSQL_*`
variables set; they drop every table in that database first.

## Deploying

Production (crowdmap.small-victories.co) runs on the Kubernetes cluster `sv-oke`, currently
switched off (0 replicas). `scripts/deploy.sh` ships `origin/main` there with the shared
`sv-deploy` script from the Oracle repo (config in `deploy.env`); `scripts/deploy.sh --check`
shows what runs. `SV_DEPLOY` overrides the script's path (default `../Oracle/deploy/sv-deploy`).

## Architecture

This project follows Clean Architecture principles:

- **Domain Layer**: Business logic and exceptions
- **Application Layer**: Commands, handlers, and use cases
- **Infrastructure Layer**: Database repositories and HTTP actions
- **Presentation Layer**: Angular frontend

## License

MIT, see [LICENSE](LICENSE).
