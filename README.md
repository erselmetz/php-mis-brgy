# PHP MIS (Barangay)

A lightweight Barangay Management Information System (MIS) built with plain PHP.

Purpose
- Provides a simple web interface for barangay administration: resident records, blotter (incident) logging, certificate requests, user accounts, and a basic dashboard.

Main features
- Resident management (add / update / view)
- Blotter entries (log, view, and print incidents)
- Certificate requests and printing
- Basic user authentication and admin pages
- Versioned API and documentation in `public/docs/`

Repository structure (important folders/files)
- `public/` — web root (views, assets, APIs)
- `includes/` — shared PHP utilities (database connection, auth, helpers)
- `schema/` — scripts to create database tables and schema
- `seed.php` — optional sample data seeder
- `config.php` / `config copy.php` — configuration (database credentials)
- `public/docs/` — API and release documentation

Quick setup
1. Requirements: PHP 7.4+ (or compatible), MySQL/MariaDB, and a web server (Apache/Nginx) or PHP built-in server.
2. Copy the configuration template and update credentials:

	`cp "config copy.php" config.php` (Windows: copy via Explorer or `copy "config copy.php" config.php`)

3. Create the database and run the schema scripts. You can run the provided schema runner:

	`php schema/run.php`

4. (Optional) Serve locally with the PHP built-in server:

	`php -S localhost:8000 -t public`

5. Open `http://localhost:8000` (or your web server host) and log in with seeded or configured admin credentials.

Notes
- Keep `config.php` out of public/remote repositories if it contains secrets.
- See `public/docs/` for API documentation and version history.

Need more?
- I can expand this README with deployment steps for XAMPP/WAMP/Docker, sample credentials, or contribution guidelines — tell me which you'd like.
