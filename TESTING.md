# PXMBoard Test Suite

## Quick Start

```bash
# Run all tests
vendor/bin/phpunit

# Unit tests only (fast, no database required)
vendor/bin/phpunit --testsuite Unit

# Integration tests only (requires test database)
vendor/bin/phpunit --testsuite Integration

# With readable test descriptions
vendor/bin/phpunit --testdox
```

## Test Database Setup

Integration tests run against a dedicated MySQL database `pxmboard_test`.
The schema is imported automatically on the first run.

1. Create the database and a user:
   ```sql
   CREATE DATABASE pxmboard_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'pxmboard_test'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL ON pxmboard_test.* TO 'pxmboard_test'@'localhost';
   ```

2. Copy the configuration template and fill in your credentials:
   ```bash
   cp phpunit.xml.dist phpunit.xml
   # Edit phpunit.xml: set TEST_DB_HOST, TEST_DB_NAME, TEST_DB_USER, TEST_DB_PASS
   ```

   `phpunit.xml` is excluded from version control (see `.gitignore`).
   `phpunit.xml.dist` contains placeholder credentials and is committed to the repository.

## Test Commands

### Basic Execution

| Command | Description |
|---------|-------------|
| `vendor/bin/phpunit` | Run all tests |
| `vendor/bin/phpunit --testsuite Unit` | Unit tests only |
| `vendor/bin/phpunit --testsuite Integration` | Integration tests only |
| `vendor/bin/phpunit tests/Unit/Parser/cPxmParserTest.php` | Single test file |
| `vendor/bin/phpunit --filter test_parse_withBoldTag` | Single test method |

### Output Formats

| Command | Output |
|---------|--------|
| `vendor/bin/phpunit` | Standard (dots/letters) |
| `vendor/bin/phpunit --testdox` | Human-readable test descriptions |
| `vendor/bin/phpunit --verbose` | Verbose output |

### Generating Reports

#### 1. Testdox Text Report
```bash
vendor/bin/phpunit --testdox-text reports/testdox.txt
cat reports/testdox.txt
```

**Content:** Readable list of all tests with `[x]` for passing tests.

#### 2. Testdox HTML Report
```bash
vendor/bin/phpunit --testdox-html reports/testdox.html
# Then open: reports/testdox.html
```

**Content:** Formatted HTML page with all test results.

#### 3. JUnit XML Report (for CI/CD)
```bash
vendor/bin/phpunit --log-junit reports/junit.xml
```

**Usage:** Jenkins, GitLab CI, GitHub Actions, etc.

#### 4. Code Coverage HTML Report
```bash
# Requires Xdebug or PCOV
# Coverage is configured in phpunit.xml and generated automatically:
vendor/bin/phpunit
# Then open: coverage/index.html

# Or generate explicitly:
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/
```

**Content:** Interactive HTML report showing tested lines.
- Green: covered lines
- Red: uncovered lines
- Percentages per file/class

#### 5. Code Coverage Text Report (terminal)
```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

## Architecture

### Test Class Hierarchy

```
PHPUnit\TestCase
  └── PxmTestCase            (superglobal helpers: $_POST, $_GET, $_SESSION; no DB)
        └── IntegrationTestCase   (real DB, transaction rollback per test, fixture helpers)
              └── ActionTestCase  (real cConfig from test DB, skin ID override)
```

**`PxmTestCase`** — Base for all tests. Resets superglobals in `setUp`/`tearDown`.

**`IntegrationTestCase`** — Wraps each test in a transaction that is rolled back in `tearDown`.
No data persists between tests. Provides fixture helpers:
- `insertBoard(array $data): int`
- `insertUser(array $data): int`
- `insertThread(int $boardId, array $data): int`
- `insertMessage(int $threadId, array $data): int`

**`ActionTestCase`** — Extends `IntegrationTestCase`. Creates a real `cConfig` from the test
database and overrides the default skin ID to match the skin available in the test schema.

### Test Suites

| Suite | Location | Depends on DB | Speed |
|-------|----------|---------------|-------|
| `Unit` | `tests/Unit/` | No | < 1 s |
| `Integration` | `tests/Integration/` | Yes (transaction rollback) | ~ 3 s |

### Integration Test Isolation

Each integration test runs inside a database transaction:
```
setUp():    START TRANSACTION
Test:       INSERT fixtures, run action or model, assert
tearDown(): ROLLBACK  →  database restored to clean state
```

No `TRUNCATE` or `DELETE` between tests — fast and reliable.

## Current Test Statistics

- **Unit tests:** 172 tests, 321 assertions
- **Integration tests:** 34 tests, 52 assertions
- **Total:** 206 tests

## Directory Structure

```
tests/
├── bootstrap.php                  # DB connection + schema import, constants
├── TestCase/
│   ├── PxmTestCase.php            # Base: superglobal helpers
│   ├── IntegrationTestCase.php    # Real DB + transaction rollback + fixtures
│   └── ActionTestCase.php         # cConfig + skin setup for action tests
├── Unit/
│   ├── Enum/                      # Enum behaviour tests
│   ├── Model/                     # Model logic tests (no DB)
│   ├── Parser/                    # Parser tests
│   └── Validation/                # Input handler and validation tests
└── Integration/
    ├── Action/                    # Action lifecycle tests (real DB + Smarty)
    └── Model/                     # Model integration tests (real DB)
```

Coverage output is written to `coverage/` (HTML) by default (configured in `phpunit.xml`).

## Development Workflow

### During Development

```bash
# 1. Run unit tests after each change (fast)
vendor/bin/phpunit --testsuite Unit --testdox

# 2. Verbose output when debugging a failure
vendor/bin/phpunit tests/Unit/Parser/cPxmParserTest.php --verbose

# 3. Full suite before committing
vendor/bin/phpunit
```

### Checking Coverage

```bash
# Coverage report is generated automatically by phpunit.xml
vendor/bin/phpunit
# Open coverage/index.html in your browser
```

### Before Committing

```bash
# 1. Format changed PHP files with Pint (PSR-12, configured in pint.json)
vendor/bin/pint src/path/to/ChangedClass.php

# 2. Full test suite
vendor/bin/phpunit

# 3. Static analysis (minimum level 5, see phpstan.neon)
vendor/bin/phpstan analyse

# 4. Write report to file
vendor/bin/phpstan analyse > reports/phpstan.txt
```

## Static Analysis

PHPStan performs static code analysis without executing the application. It is configured in `phpstan.neon` at the project root.

```bash
# Run analysis (outputs to terminal)
vendor/bin/phpstan analyse

# Run and write report to file
vendor/bin/phpstan analyse --output-file reports/phpstan.txt

# Show full error details
vendor/bin/phpstan analyse --level 5
```

The minimum required level is **5**. Results are written to `reports/` which is gitignored.

## Report Directories

After a test run:

```
PXMBoard/
├── coverage/           # HTML coverage report
│   └── index.html     # Open in browser
├── reports/                      # All generated reports (gitignored)
│   ├── testdox.txt               # PHPUnit: --testdox-text reports/testdox.txt
│   ├── testdox.html              # PHPUnit: --testdox-html reports/testdox.html
│   ├── junit.xml                 # PHPUnit: --log-junit reports/junit.xml
│   ├── phpstan.txt               # PHPStan: vendor/bin/phpstan analyse --output-file reports/phpstan.txt
│   ├── playwright-html/          # Playwright HTML report
│   └── playwright-artifacts/     # Playwright screenshots & traces (failed tests only)
└── .phpunit.cache/               # PHPUnit cache (safe to ignore)
```

## Installing Xdebug / PCOV (for Coverage)

### Xdebug (development)
```bash
sudo apt install php8.5-xdebug
```

### PCOV (faster, coverage only)
```bash
sudo apt install php8.5-pcov
```

After installation, coverage is available:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Troubleshooting

### "Class not found" error
```bash
composer dump-autoload
```

### PHPUnit version check
```bash
vendor/bin/phpunit --version
# Expected: PHPUnit 13.0.x
```

### Integration tests fail with "connection refused"
- Check that `phpunit.xml` exists and contains valid credentials.
- Verify the `pxmboard_test` database exists: `mysql -u root -e "SHOW DATABASES;"`

### Slow tests
```bash
# Run unit tests only (no database)
vendor/bin/phpunit --testsuite Unit
```

## CI/CD Integration

### GitHub Actions Example
```yaml
- name: Run Tests
  run: vendor/bin/phpunit --testdox --log-junit junit.xml

- name: Upload Test Results
  uses: actions/upload-artifact@v3
  with:
    name: test-results
    path: junit.xml
```

### GitLab CI Example
```yaml
test:
  script:
    - vendor/bin/phpunit --testdox --log-junit junit.xml
  artifacts:
    reports:
      junit: junit.xml
```

---

## E2E Tests (Playwright)

End-to-end tests run against a real PHP server and a dedicated E2E database.
Playwright drives a real browser and tests the complete request-response cycle
including HTMX navigation flows.

### One-Time Setup

`composer dev-setup` handles most steps automatically (npm install, Playwright browser binaries).
Manual steps required afterwards:

**1. Install WebKit system dependencies** (Linux only, requires sudo):
```bash
sudo env "PATH=$PATH" npx playwright install-deps webkit
```
> `npx playwright install` (called by `composer dev-setup`) installs the browser
> *binaries*, but on Linux the WebKit engine also needs OS-level libraries
> (`libgtk-4`, `libgraphene`, etc.) that must be installed via the system package
> manager. This step is a no-op on macOS and Windows.

**2. Create the E2E database:**
```sql
CREATE DATABASE pxmboard_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON pxmboard_e2e.* TO 'pxmboard'@'localhost';
```

**3. Fill in the E2E credentials** (`tests/E2E/.env` was created from the example by `composer dev-setup`):
```bash
# Edit tests/E2E/.env: set E2E_DB_HOST, E2E_DB_USER, E2E_DB_PASS, E2E_DB_NAME
# On Linux with a Unix socket, also set E2E_DB_SOCKET
```

### PHP Server Lifecycle

Playwright fully manages the PHP development server on port 8001 — starting it
via `webServer` in `playwright.config.js` and stopping it via `global-teardown.js`
after every test run. **No manual server start is required.**

### Typical Test Workflow

```bash
# All tests (all 8 browser projects, headless)
npm run test:e2e

# Desktop Chrome only (faster for development cycles)
npx playwright test --project='Desktop Chrome'

# Single spec file
npx playwright test tests/E2E/specs/03_auth.spec.js --project='Desktop Chrome'

# Reset the E2E database without running tests
npm run test:e2e:reset-db
```

Every `test:e2e*` script resets the E2E database to a clean seed state before launching Playwright.

### All npm Scripts

| Command | Description |
|---------|-------------|
| `npm run test:e2e` | All 8 projects (headless) |
| `npm run test:e2e:ui` | Interactive Playwright UI mode |
| `npm run test:e2e:headed` | Headed (visible browser windows) |
| `npm run test:e2e:desktop` | Desktop Chrome + Safari (light + dark) |
| `npm run test:e2e:mobile` | Mobile iPhone 14 Pro + Pixel 7 (light + dark) |
| `npm run test:e2e:safari` | All 4 Safari/WebKit projects (Desktop + Mobile, light + dark) |
| `npm run test:e2e:pwa` | PWA / Service Worker spec only |
| `npm run test:e2e:reset-db` | Reset E2E DB without running tests |
| `npm run test:e2e:report` | Open HTML report in browser (after a test run) |

### Results and Artifacts

| Path | Content |
|------|---------|
| `reports/playwright-html/index.html` | Full HTML report with screenshots and traces |
| `reports/playwright-artifacts/` | Screenshots and traces of failed tests |

**Open the HTML report:**
```bash
npm run test:e2e:report
# opens http://localhost:9323 — shows screenshots and step-by-step traces
```

> **Pitfall — `--reporter=html` on the CLI writes to the wrong folder.**
> When you run `npx playwright test --reporter=html`, the CLI flag **replaces** the
> entire reporter configuration from `playwright.config.js`. Playwright then falls back
> to its built-in default output folder `playwright-report/` (in the project root),
> ignoring the configured `outputFolder: 'reports/playwright-html'`.
>
> Always run tests without the `--reporter` override (e.g. `npm run test:e2e` or
> plain `npx playwright test`), so that `playwright.config.js` controls both the
> reporter and the output folder. Use `--reporter=dot` or `--reporter=line` only when
> you explicitly **don't** want an HTML report (e.g. for quick smoke checks).

**Alternative: VS Code Playwright Extension** (`ms-playwright.playwright`, recommended in `.vscode/extensions.json`)
— shows results, screenshots and traces directly in the VS Code Testing sidebar without a browser server.

### Debugging

**Headed + SlowMo** — watch what the browser does:

Uncomment the prepared line in the `use` block of `playwright.config.js`:
```js
// launchOptions: { slowMo: 1500 },
```
Then:
```bash
npx playwright test tests/E2E/specs/06_private-messages.spec.js \
    --project='Desktop Chrome' --headed
```
**Re-comment before committing.**

**Step-by-step debug mode** (pauses at every action):
```bash
PWDEBUG=1 npx playwright test tests/E2E/specs/... --headed
```

**After an aborted debug run** (teardown hook did not run):
```bash
pkill -f "php -S 127.0.0.1:8001"
npm run test:e2e:reset-db
```

### Playwright Projects

| Project | Browser | Viewport | Color scheme |
|---------|---------|----------|--------------|
| Desktop Chrome | Chromium | 1280×720 | light |
| Desktop Chrome Dark | Chromium | 1280×720 | dark |
| Desktop Safari | WebKit | 1280×720 | light |
| Desktop Safari Dark | WebKit | 1280×720 | dark |
| Mobile Safari – iPhone 14 Pro | WebKit | 393×852 | light |
| Mobile Safari – iPhone 14 Pro Dark | WebKit | 393×852 | dark |
| Mobile Chrome – Pixel 7 | Chromium | 412×915 | light |
| Mobile Chrome – Pixel 7 Dark | Chromium | 412×915 | dark |

### Directory Structure

```
tests/E2E/
├── fixtures/
│   ├── e2e-seed.sql        # Full schema + deterministic test data
│   ├── reset-db.js         # Drops, re-creates and seeds the E2E DB
│   └── auth.js             # Login helpers
├── pages/                  # Page Object Models
│   ├── BoardPage.js        # Board list, login/logout
│   ├── ThreadListPage.js   # Thread list (#threadlist-container)
│   ├── ThreadPage.js       # Thread view (#thread-container)
│   └── MessagePage.js      # Compose / reply form
└── specs/
    ├── 01_board-list.spec.js       # Board list, login form
    ├── 02_navigation.spec.js       # HTMX navigation flow
    ├── 03_auth.spec.js             # Login / logout / access control
    ├── 04_post-message.spec.js     # Post / reply / draft
    ├── 05_search.spec.js           # Full-text search
    ├── 06_private-messages.spec.js # PM inbox / outbox / send
    └── 07_pwa.spec.js              # Service Worker, manifest, dark mode
```

### Seed Data

| Resource | Details |
|----------|---------|
| Users | `Webmaster` (admin, password `test1234`), `Tester` (password `test5678`) |
| Boards | `Test` (id=1), `Test2` (id=2) |
| Threads | Thread 1 with 3 messages (published), Thread 4 pinned |
| PM | 1 unread PM from Webmaster to Tester |

### Architecture: HTMX-First Testing

Tests always navigate via **full board URLs**, never via direct partial URLs.
HTMX loads the thread list and messages as partials into their respective containers
(`#threadlist-container`, `#message-container`) — exactly as a real user would see them.

```js
// Correct: full URL, then wait for HTMX swap
await page.goto(`/pxmboard.php?mode=board&brdid=1&thrdid=1&msgid=1`);
await page.locator('#message-container').waitFor({ state: 'visible' });

// Wrong: load partial directly (bypasses session/layout context)
await page.goto(`/pxmboard.php?mode=message&brdid=1&msgid=1`);
```

### Keeping DB Schema in Sync

Every change to the application DB schema must be applied to all schema files:

| File | Purpose |
|------|---------|
| `install/sql/pxmboard-mysql.sql` | Production install schema — the authoritative source of truth |
| `install/sql/upgrade-X.Y.Z.sql` | Incremental migration |
| `tests/E2E/fixtures/e2e-seed.sql` | E2E schema + seed data |
| `tests/bootstrap.php` | PHPUnit integration test schema |

A story that changes the DB schema is not complete until all four files are updated and the tests are green.

#### e2e-seed.sql must mirror pxmboard-mysql.sql exactly

The `CREATE TABLE` definitions in `tests/E2E/fixtures/e2e-seed.sql` **must be identical** to those in `install/sql/pxmboard-mysql.sql`. "Identical" means:

- Same column names, data types (`BOOLEAN`, `MEDIUMINT UNSIGNED`, `SMALLINT UNSIGNED`, `TINYINT UNSIGNED`, etc.) and sizes
- Same `DEFAULT` values, `NULL`/`NOT NULL` constraints, and inline `COMMENT` strings
- Same indexes (names and covered columns)
- Same `ENGINE`, `DEFAULT CHARSET`, `COLLATE`, and table-level `COMMENT`
- No extra columns that do not exist in the canonical schema

Only the `INSERT` statements in the static-data and seed-data sections of `e2e-seed.sql` are allowed to differ (e.g., a valid webmaster email for E2E tests instead of an empty string).

**When you change the schema:** update `pxmboard-mysql.sql` first, then copy the affected `CREATE TABLE` block verbatim into `e2e-seed.sql` (replacing the old one) and run `node tests/E2E/fixtures/reset-db.js` to verify the seed imports without errors.
