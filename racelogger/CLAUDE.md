# CLAUDE.md — Project Intelligence

## Project Overview
- **Framework:** Laravel (PHP)
- **Database:** MySQL
- **Primary Goal:** Racing Sim Result Logger

## Technical Standards & Patterns
- **Database Logic:** Always use **Laravel Eloquent** for database interactions. Avoid raw SQL unless specifically requested for performance optimization.
- **Workflow:** Before proposing new features or logic, **always scan the `app/Models` directory** to understand existing database relationships and schemas.
- **Code Style:** Follow PSR-12 coding standards.

## Documentation Requirements
- **PHP Functions:** Every new function added must include a concise DocBlock or inline comment explaining its purpose, parameters, and return type.
- **Views/HTML:** Do **not** add comments to Blade templates or HTML files unless the logic is exceptionally complex. Keep frontend code clean and uncommented.

## Common Commands
- **Serve:** `php artisan serve`
- **Migrate:** `php artisan migrate`
- **Test:** `php artisan test`
- **Cache Clear:** `php artisan config:cache && php artisan route:cache`

## Codebase Index

Before making changes, consult the relevant index file in `docs/`:

| File | What it covers |
|------|---------------|
| [`docs/routes.md`](docs/routes.md) | Every web route — method, URI, controller action, middleware |
| [`docs/pages.md`](docs/pages.md) | Full view/page tree with server vs client flags and partials |
| [`docs/lib.md`](docs/lib.md) | All service classes and controllers with public method signatures |
| [`docs/schema.md`](docs/schema.md) | Database schema — tables, key columns, types, foreign keys, model relationships |
| [`docs/components.md`](docs/components.md) | Blade partials (required variables) and React components (props) |

> These files are manually maintained. Update the relevant doc when adding routes, services, schema changes, or new components.

## Project Specifics
- [Add any specific API keys or external service names here for quick reference]