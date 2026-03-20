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

## Project Specifics
- [Add any specific API keys or external service names here for quick reference]