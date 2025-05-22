# Ryvr Core - Marketing Automation Platform

Ryvr is an automation layer for small-business marketing, providing workflow automation for various marketing tools and services.

## Description

The Ryvr platform connects various marketing tools and services, allowing users to create automated workflows for their marketing needs. Built as a WordPress plugin, Ryvr provides a powerful and intuitive interface for managing marketing automation tasks.

Key features:
- Workflow-based automation
- Integration with popular marketing platforms
- AI-powered content generation and analysis
- Detailed reporting and analytics
- Multi-user support with agency capabilities

## Requirements

- WordPress 6.5 or higher
- PHP 8.2 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)

## Installation

### Manual Installation

1. Download the zip file from the repository
2. Upload to your WordPress plugins directory
3. Activate the plugin through the WordPress admin interface

### Composer Installation

```bash
composer require ryvr/ryvr-core
```

## Configuration

1. Navigate to the Ryvr settings page in your WordPress admin
2. Configure your API keys for the various services
3. Set up your first workflow

## Development

Ryvr is built with modern PHP practices and follows PSR-12 coding standards.

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/schuttebj/ryvr-beta.git

# Install dependencies
cd ryvr-beta
composer install
```

### Run Tests

```bash
composer test
```

### Check Code Quality

```bash
composer phpcs
composer phpstan
```

## Documentation

For detailed documentation, see the [Ryvr Documentation](https://github.com/schuttebj/ryvr-beta/wiki).

## License

GPL v2 or later

## Support

For support, please open an issue in the GitHub repository. 