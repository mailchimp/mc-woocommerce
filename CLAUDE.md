# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Mailchimp for WooCommerce WordPress plugin that syncs WooCommerce store data with Mailchimp for email marketing campaigns.

**Requirements:**
- PHP 7.4+ (PHP 8 supported)
- WordPress 6.2+ (tested up to 6.8)
- WooCommerce 8.2+ (tested up to 9.8)
- WP_CRON must be enabled for data synchronization

## Build Commands

### PHP Development
```bash
# Install PHP dependencies
composer install

# Check PHP compatibility
vendor/bin/phpcs --standard=PHPCompatibilityWP *.php
```

### JavaScript Development (blocks directory)
```bash
cd blocks/

# Install dependencies
npm install

# Development
npm run start          # Development mode with watch
npm run build          # Production build
npm run lint:js        # Lint JavaScript
npm run lint:js-fix    # Fix linting issues
npm run test:unit      # Run unit tests
```

## Codebase Architecture

### Core Plugin Structure
- **`mailchimp-woocommerce.php`**: Main plugin file with WordPress plugin headers
- **`bootstrap.php`**: Custom SPL autoloader for plugin classes
- **`includes/`**: Core functionality
  - `api/`: Mailchimp API integration, transformers, error handling
  - `processes/`: Background sync jobs for products, customers, orders, coupons
  - `class-mailchimp-woocommerce.php`: Main plugin class
  - `class-mailchimp-woocommerce-service.php`: Service class handling initialization
- **`admin/`**: Admin interface (v2 subdirectory contains updated UI)
- **`blocks/`**: Gutenberg blocks for newsletter subscription
- **`public/`**: Frontend functionality and GDPR scripts

### Key Technical Details
- Uses Action Scheduler for background job processing
- Custom REST API endpoints for external integration
- Supports WooCommerce HPOS (High Performance Order Storage)
- WPML compatible for multilingual stores
- Uses WordPress transients for caching
- Implements queue-based sync system for large stores

### Data Sync Architecture
The plugin syncs data in batches using background jobs:
1. **Initial sync**: Full store data export on first activation
2. **Real-time sync**: Updates sent on order/customer/product changes
3. **Queue management**: Action Scheduler handles job processing
4. **CLI mode**: Available for larger stores via WP-CLI

### Development Patterns
- Follow WordPress coding standards (enforced via PHPCS)
- Use WordPress hooks system for extensibility
- Namespace: `MailChimp_WooCommerce` for main classes
- API classes use `MailChimp_WooCommerce_Transform_` prefix
- Background jobs extend `Abstract_Sync` class
- All strings should be internationalized using `__()` or `_e()`

### Testing
- JavaScript unit tests via `npm run test:unit` in blocks directory
- No PHP unit tests currently implemented
- Manual testing required for WordPress/WooCommerce integration

### Important Constants
- `MAILCHIMP_HIGH_PERFORMANCE`: Enable high-performance mode
- `MC_WC_VERSION`: Plugin version constant
- Various feature flags for debugging and performance tuning

## Common Development Tasks

### Adding New Sync Functionality
1. Create new transformer in `includes/api/assets/`
2. Extend `Abstract_Sync` class in `includes/processes/`
3. Register job with Action Scheduler
4. Add appropriate hooks in main plugin class

### Working with Blocks
1. Navigate to `blocks/` directory
2. Use `npm run start` for development
3. Edit React components in `src/` directory
4. Build with `npm run build` before testing in WordPress

### Debugging
- Enable WordPress debug mode
- Check Action Scheduler admin page for job status
- Use browser developer tools for REST API calls
- Review error logs in WooCommerce status page