# Talentia

Laravel 12 application using Livewire, FilamentPHP v5, Tailwind CSS, and Vite.

## About

# ESP
Gestión de CVs para descartar candidatos que no sean aptos según los requisitos de la oferta. El proceso se hace a través de la IA, que recibe el documento PDF y los requisitos y la IA gestiona y decide si el candidato es APTO o NO APTO.
# ENG
CV screening to identify candidates who do not meet the job requirements. The process is carried out using AI, which receives the PDF document and the job requirements, and then assesses and determines whether the candidate is SUITABLE or NOT SUITABLE.

## Features

- Gestión de ofertas con criterios de filtrado (prompt) y configuración opcional de sincronización con CRM externo.
- Carga masiva de CVs en PDF por oferta y evaluación automática con IA (Gemini).
- Clasificación de candidatos como APTO/NO APTO con análisis y datos extraídos (nombre, email, teléfono, titulación).
- Panel de leads con filtros por oferta y estado, y detalle con análisis de IA y enlace al CV.
- Sincronización opcional de leads con Clientify (contacto + deal) desde el panel.

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- A database (SQLite works out of the box)

## Setup

```bash
composer install
composer require filament/filament:^5.0
composer run setup
```

The `setup` script installs PHP and JS dependencies, creates `.env`, generates an app key, runs migrations, and builds assets.

If you prefer manual steps:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

## Development

```bash
composer run dev
```

Runs the Laravel dev server, queue worker, and Vite dev server concurrently.

## Testing and Linting

```bash
composer run test
```

```bash
composer run lint
```

## Production Build

```bash
npm run build
```

## Environment Notes

- Default SQLite database file: `database/database.sqlite`
- Config values live in `.env` (see `.env.example`)

## License

MIT



