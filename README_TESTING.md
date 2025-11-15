# Testing Setup Guide
## MIS Barangay - Unit Testing

### Prerequisites

1. **PHPUnit Installation**
   ```bash
   composer require --dev phpunit/phpunit
   ```

   Or install globally:
   ```bash
   composer global require phpunit/phpunit
   ```

2. **PHP Version**
   - PHP 7.4 or higher required
   - Extensions: mysqli, mbstring

### Running Tests

#### Run All Tests
```bash
php vendor/bin/phpunit
```

#### Run Specific Test Suite
```bash
php vendor/bin/phpunit tests/Unit/AuthTest.php
php vendor/bin/phpunit tests/Unit/DatabaseTest.php
```

#### Run with Coverage
```bash
php vendor/bin/phpunit --coverage-html tests/coverage/html
```

Coverage report will be available at: `tests/coverage/html/index.html`

### Test Structure

```
tests/
├── bootstrap.php          # Test environment setup
├── Unit/                  # Unit tests
│   ├── AuthTest.php      # Authentication tests
│   └── DatabaseTest.php  # Database operation tests
└── coverage/              # Coverage reports (generated)
    ├── html/             # HTML coverage report
    └── coverage.txt      # Text coverage report
```

### Writing Tests

1. **Create test file** in `tests/Unit/`
2. **Extend PHPUnit\Framework\TestCase**
3. **Use setUp()** for test initialization
4. **Use tearDown()** for cleanup
5. **Follow naming:** `ClassNameTest.php`

Example:
```php
class MyFeatureTest extends PHPUnit\Framework\TestCase
{
    public function testFeatureWorks()
    {
        $this->assertTrue(true);
    }
}
```

### Test Coverage

- Target: 70%+ coverage for core functions
- Focus areas:
  - Authentication functions
  - Database operations
  - Data validation
  - Security functions

### Continuous Integration

Add to `.github/workflows/tests.yml` (if using GitHub Actions):
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: php vendor/bin/phpunit
```

---

**Note:** Update `phpunit.xml` configuration as needed for your environment.

