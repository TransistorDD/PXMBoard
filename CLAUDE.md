# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PXMBoard is a self-hosted PHP 8.4+ forum (no framework) with HTMX-powered UI, Smarty templates, and direct SQL via a custom database abstraction layer. Namespace: `PXMBoard\`.

## Common Commands

```bash
# Development setup (composer install + npm install + frontend build)
composer dev-setup

# Local dev server
composer serve                    # http://localhost:8000

# Static analysis (level 6, must pass before pushing)
vendor/bin/phpstan analyse

# Code formatting (PSR-12)
vendor/bin/pint src/

# Tests
vendor/bin/phpunit                          # all tests
vendor/bin/phpunit --testsuite Unit         # unit tests (no DB, fast)
vendor/bin/phpunit --testsuite Integration  # integration tests (needs test DB)
vendor/bin/phpunit --filter test_methodName # single test

# Frontend
npm run build          # CSS + libs + Vite bundle
npm run dev            # Vite watch mode
```

## Architecture

### Request Flow

`public/pxmboard.php` is the single entry point. It initializes DB, config, session, i18n, then routes via the `mode` parameter:

- `mode=login` → `PXMBoard\Controller\Board\cActionLogin`
- `mode=admUserlist` → `PXMBoard\Controller\Admin\cAdminActionUserlist`
- `mode=ajaxThreadlist` → `PXMBoard\Controller\Ajax\cAjaxActionThreadlist`

Each action follows a lifecycle: `validateBasePermissionsAndConditions()` → `doPreActions()` → `performAction()` → `doPostActions()` → `getOutput()`.

### Action Hierarchy

- `cBaseAction` (abstract) — permission checks, CSRF, input/server handlers
  - `cPublicAction` — renders HTML via Smarty templates (`skins/pxm/*.tpl`)
  - `cAjaxAction` — returns JSON/partial HTML for HTMX requests
  - `cAdminAction` — admin panel interface

### Key Directories

- `src/Controller/{Board,Admin,Ajax}/` — request handlers (82 classes)
- `src/Model/` — data models with direct SQL (load/create/update/delete patterns)
- `src/Database/` — DB abstraction (`cDBFactory` singleton, `cDB`, `cDBResultSet`)
- `src/Enum/` — PHP enums with `getLabel()` returning translated text
- `src/Parser/` — PXM markup ↔ HTML conversion
- `src/Validation/` — input handling (`cInputHandler`, `cServerHandler`)
- `src/I18n/` — `cTranslator::translate('key', ['param' => 'value'])`
- `src/Search/` — search engine abstraction (MySQL FULLTEXT or ElasticSearch)
- `skins/pxm/` — Smarty templates
- `lang/de.php` — German translations (dot-notation keys)
- `build/` — Tiptap editor JS source (Vite bundles to `public/js/editor-bundle.js`)

### Database

No ORM. Uses `cDBFactory::getInstance()` → `executeQuery(sql, limit, offset)` → `cDBResultSet`. Tables prefixed `pxm_`, columns prefixed `c_`. Escape with `$objDb->quote()`.

### Test Hierarchy

`PxmTestCase` (superglobal helpers) → `IntegrationTestCase` (real DB + transaction rollback + fixtures) → `ActionTestCase` (config + skin setup). Integration tests use `insertBoard()`, `insertUser()`, `insertThread()`, `insertMessage()` fixture helpers.

## Coding Conventions

- `declare(strict_types=1)` in every file
- Class names: `c` prefix + PascalCase (`cUser`, `cBoardMessage`)
- Properties: `m_` prefix + type hint (`m_i` int, `m_s` string, `m_b` bool, `m_obj` object, `m_arr` array)
- Private/protected methods: `_` prefix (`_requireAuthentication()`)
- All user-facing text via `cTranslator::translate()`, never hardcoded
- State-changing actions must validate CSRF token via `_requireValidCsrfToken()`
- User input only via `cInputHandler`/`cServerHandler`, never raw superglobals

## Known Pitfalls

### htmx: `htmx.ajax()` and `hx-sync` are incompatible (htmx 2.0.8)

`htmx.ajax()` registers internal request state on the target element but does not clean it up after completion. If other elements use `hx-sync="#target:replace"` on the same target, they find this stale state, send `htmx:abort`, and the resulting state blocks new requests. **Do not use `hx-sync` on elements that target the same container as `htmx.ajax()` calls.** Example: `loadThreadAndMessage()` uses `htmx.ajax()` targeting `#message-container`, so thread tree links in `thread.tpl` must not have `hx-sync="#message-container:..."`.

### Smarty template cache

After changing `.tpl` files, clear the compiled cache: `rm -f skins/pxm/cache/*.php`
