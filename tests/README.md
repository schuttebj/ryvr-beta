# Ryvr Tests

This directory contains tests for the Ryvr plugin.

## Running Tests

To run the tests, use the following command:

```bash
composer test
```

### Unit Tests

Unit tests are located in the `Unit` directory and test individual components of the plugin.

## Test Structure

- `Unit/` - Unit tests for individual components
  - `Security/` - Tests for security components
  - `Connectors/` - Tests for connector components
    - `OpenAI/` - Tests for OpenAI connector
    - `DataForSEO/` - Tests for DataForSEO connector
  - `Admin/` - Tests for admin components

## Adding New Tests

1. Create a new test file in the appropriate directory
2. Name the file `{ClassUnderTest}Test.php`
3. Extend `PHPUnit\Framework\TestCase`
4. Write your tests

## Mocking WordPress

The `bootstrap.php` file provides mocks for WordPress functions. If you need to mock additional WordPress functions, add them to this file.

For more complex WordPress testing, consider using [Brain\Monkey](https://brain-wp.github.io/BrainMonkey/) or [WP_Mock](https://github.com/10up/wp_mock). 