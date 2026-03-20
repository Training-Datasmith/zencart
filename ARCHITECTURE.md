# Zen Cart Architecture

## Purpose

Zen Cart is a PHP e-commerce platform derived from osCommerce, providing a full-featured storefront and admin panel. The codebase spans PHP 7.4–8.x, uses `strict_types`, and has an event/notifier system for extensibility.

## Directory Structure

```
zencart/
├── admin/                          # Admin panel entry points and JS helpers
│   └── includes/
│       └── javascript/             # Admin-side JS generation (PHP-templated)
├── includes/
│   ├── classes/                    # Core business logic
│   │   ├── ajax/                   # Ajax handler classes (zc_Ajax_*)
│   │   ├── db/mysql/               # Database abstraction (query_factory)
│   │   ├── DbRepositories/         # Repository pattern for DB access
│   │   ├── Exceptions/             # Domain exception classes
│   │   ├── vendors/                # Bundled third-party libraries (PHPMailer, BaconQrCode…)
│   │   ├── breadcrumb.php          # Breadcrumb trail builder
│   │   ├── currencies.php          # Currency formatting and conversion
│   │   ├── class.base.php          # Base class with notifier/observer hooks
│   │   └── class.zc_Password.php   # Password hashing wrapper
│   ├── languages/                  # Language/translation files per template
│   │   └── english/
│   │       └── html_includes/      # Per-page HTML define files (classic, responsive_classic)
│   ├── modules/pages/              # Per-page JS injection modules
│   └── templates/                  # Theme templates (template_default, responsive_classic…)
└── index.php                       # Front controller
```

## Key Design Decisions

- **`base` class + notifier system**: All major classes extend `base` (from `class.base.php`), which implements an observer/notifier pattern. Observers register for named events (e.g. `NOTIFY_BREADCRUMB_RESET`) and can mutate data.
- **Global `$db`**: Database access is via the global `$db` query factory (`includes/classes/db/mysql/query_factory.php`). Queries return result objects.
- **Repository pattern**: `DbRepositories/` classes provide a cleaner layer over `$db` for specific entities (plugins, configuration, product type layouts).
- **Exception hierarchy**: `Exceptions/` contains domain-specific exception classes (`Plugin_Installer_Exception`, `Search_Exception`) extending SPL exceptions.
- **Template system**: Each template lives under `includes/templates/{template_name}/`. The active template is set in the DB and loaded at runtime.
- **Language defines**: Translation strings are PHP `define()` calls loaded into global scope from per-page language files.
- **`DISABLE_BREADCRUMB_LINKS_ON_LAST_ITEM`**: Controlled by a constant that can be overridden in `extra_datafiles/`.

## Extension Points

- Add an observer: create a class with a `update()` method and register it using `$this->attach($observer, 'EVENT_NAME')`.
- Add a page module: create `includes/modules/pages/{page_name}/` with JS injection files.
- Add a template: create `includes/templates/{name}/` following the template directory structure.
- Add a plugin: use the Plugin Control system under `DbRepositories/Plugin_Control*`.

## Dependency Flow

```
index.php
  → application_top.php (DB, session, language, currencies, template bootstrap)
  → Page-specific module includes
  → notifier events throughout
  → Template render
  → application_bottom.php
```
