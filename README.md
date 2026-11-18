# Zijpalm Application Example

A Laravel 12 example application for activities, membership, a webshop, charity requests, and a football tournament. This repository is a development starting point, not a production-ready deployment.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- A database supported by Laravel

## Local setup

1. Install the PHP and JavaScript packages:

   ```powershell
   composer install
   npm ci
   ```

2. Create a local environment file and an application key:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Set the database connection, `ADMIN_EMAIL`, and a unique `ADMIN_PASSWORD` in `.env`. The database seeder intentionally stops if an administrator password is missing.

4. Build the front-end assets and create the database tables and sample content:

   ```powershell
   npm run build
   php artisan migrate --seed
   ```

5. Start the local development services:

   ```powershell
   composer run dev
   ```

To run the tests, use `composer test`.

## Sample data and deployment

Seed data is for local examples only. Review and replace it before using this code for another organization. User-uploaded files, original Word documents, local environment files, and production deployment details are intentionally not included. Add suitable test assets and deployment instructions for your own environment.

Never use local sample accounts or development settings in production. Review access control, privacy, backups, mail, payment settings, and deployment configuration before any real use.
