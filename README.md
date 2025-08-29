# Laravel Auditable

**Laravel Auditable** is a lightweight, flexible package for tracking changes to your Eloquent models. It automatically logs **create** and **update** actions, including:

- **Binary IP storage** for fast lookups (IPv4 & IPv6)
- **Deduplicated User-Agent table** to save space
- **Automatic `created_by` / `updated_by` population**
- **Audit logs export** to JSON or CSV
- **Retention-based cleanup** of old logs
- **API endpoint** to fetch model audit history

It’s designed to be **easy to install, reusable across projects, and highly configurable** via a simple config file.

---

## Features

- Track **who** changed a model and **when**
- Store IP and User-Agent efficiently
- Keep your tables clean (no extra columns per model required)
- Export and archive old logs automatically
- Optional **API endpoint** for retrieving audit history
- Configurable retention period (`retention_days`)

---

## Installation

```bash
composer require yourname/laravel-auditable

php artisan vendor:publish --provider="YourName\Auditable\AuditableServiceProvider" --tag=config
php artisan migrate
