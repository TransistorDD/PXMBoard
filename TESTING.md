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
vendor/bin/phpstan analyse --output-file reports/phpstan.txt
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
├── reports/           # All generated reports (gitignored)
│   ├── testdox.txt    # PHPUnit: --testdox-text reports/testdox.txt
│   ├── testdox.html   # PHPUnit: --testdox-html reports/testdox.html
│   ├── junit.xml      # PHPUnit: --log-junit reports/junit.xml
│   └── phpstan.txt    # PHPStan: vendor/bin/phpstan analyse --output-file reports/phpstan.txt
└── .phpunit.cache/    # PHPUnit cache (safe to ignore)
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
