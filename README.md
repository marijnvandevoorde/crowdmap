# Crowdmap

A collaborative mindmapping application built with PHP and Angular.

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

## Running Tests

```bash
vendor/bin/phpunit
```

The integration tests run against MySQL with `TEST_DB=mysql` and the `MYSQL_*`
variables set; they drop every table in that database first.

## Architecture

This project follows Clean Architecture principles:

- **Domain Layer**: Business logic and exceptions
- **Application Layer**: Commands, handlers, and use cases
- **Infrastructure Layer**: Database repositories and HTTP actions
- **Presentation Layer**: Angular frontend

## License

Private project
