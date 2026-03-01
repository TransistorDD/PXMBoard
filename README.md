# PXMBoard

A lightweight, self-hosted PHP forum. Thread-based discussion boards with private
messaging, user management, and moderator support — running entirely on PHP and MySQL,
no framework required.

This project is the continuation and modernisation of
[PXMBoard 2.5](https://sourceforge.net/projects/pxmboard/), originally released in 2001
and last updated in 2007. Development resumed in 2025 with a focus on PHP 8 compatibility,
security hardening, and a modernised UI — while keeping the original architecture intact.

---

## Features

- **Boards, threads & messages** — multi-board layout, hierarchical thread view
- **Private messaging** — inbox, outbox, unread counters
- **User accounts** — registration (direct or admin-approved), profile images, signatures
- **Moderator & admin system** — per-board moderators, admin panel
- **HTMX-powered UI** — SPA-like navigation without a full page reload
- **PWA support** — installable on mobile and desktop
- **In-app notifications** — reply notifications, @-mentions with autocomplete
- **Rich text editor** — Tiptap WYSIWYG with formatting and quote support
- **Read status tracking** — per-user unread indicators for threads
- **Message drafts** — save and resume draft posts
- **Multi-device login** — persistent login tickets with device management
- **DBMS support** — MySQL & PostgreSQL; others possible
- **Search** — MySQL `FULLTEXT` search or ElasticSearch optional
- **Skinnable** — Smarty template engine; XSLT support optional
- **Security** — bcrypt passwords, secure token generation

---

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | ≥ 8.4 |
| MySQL / MariaDB | MySQL 5.6 / MariaDB 10.0.5 |
| PHP extensions | `mysqli`, `mbstring`, `json` |
| Composer | ≥ 2.0 |
| PHP extensions (optional) | `xsl` (XSLT skin) |

Install Composer dependencies before first use:

```bash
composer install --no-dev
```

---

## Quick Start

See **[install/INSTALL.md](install/INSTALL.md)** for the full installation guide.

Short version:

1. Point your web server's document root to `public/`.
2. Open `https://yourdomain/install/install.php` in your browser.
3. Follow the installer: database credentials, admin account, schema import.
4. The installer deletes itself and redirects to the board.

---

## Configuration

The installer writes two files automatically:

| File | Purpose |
|------|---------|
| `config/pxmboard-config.php` | Database, template engine, session settings |
| `public/pxmboard-basedir.php` | Defines the `BASEDIR` constant |

To configure manually, copy the template and edit it:

```bash
cp config/pxmboard-config.example.php config/pxmboard-config.php
```

Key settings in `config/pxmboard-config.php`:

```php
'database' => [
    'type' => 'MySql',       // MySql | PostgreSql
    'host' => 'localhost',
    'user' => 'pxmboard',
    'pass' => 'your-password',
    'name' => 'pxmboard'
],
'template_types' => ['Smarty'],   // Smarty | Xslt
'search_engine'  => ['type' => 'MySql'],  // MySql | ElasticSearch
'session_name'   => 'brdsid'
```

Board-level settings (skin, pagination, mail templates, etc.) are managed through
the **Administration** panel after login.

---

## Development

**Prerequisites:** PHP ≥ 8.4, Composer ≥ 2.0, Node.js / npm

1. Clone the repository
2. Run the development setup:
   ```bash
   composer dev-setup
   ```
3. Edit `phpunit.xml` and set your local test database credentials (see [TESTING.md](TESTING.md))

---

## Tests

The test suite uses PHPUnit 13 against a dedicated `pxmboard_test` database.

```bash
# Install dependencies first
composer install

# Run all tests
vendor/bin/phpunit

# Unit tests only (no database required)
vendor/bin/phpunit --testsuite Unit
```

For setup instructions, database configuration, and test architecture see
**[TESTING.md](TESTING.md)**.

---

## Changelog

See **[install/CHANGELOG.md](install/CHANGELOG.md)**.

---

## Licence

PXMBoard is free software distributed under the
**GNU General Public License v3.0 or later**.
See **[LICENCE](LICENCE)** for the full licence text.

Copyright 2001–2026 Torsten Rentsch
