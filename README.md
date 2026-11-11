# Equipment Booking API

A RESTful Laravel API for managing equipment and shared resources. Organisations can register resources (laptops, projectors, cameras, meeting rooms), track quantities, and run a booking workflow where requests are approved or rejected by managers, with double-booking prevention.

## Features

- Token-based authentication (Laravel Sanctum)
- Role-based access control: **Admin**, **Resource Manager**, **Employee**
- Resource and category management with search, filtering and pagination
- Date-range availability, computed from approved bookings
- Booking workflow: Pending, Approved, Rejected, Cancelled, Completed
- OpenAPI documentation generated automatically (Scramble)
- MySQL with foreign keys and CHECK constraints

## Tech stack

| Component | Choice |
|---|---|
| Framework | Laravel (PHP 8.2+) |
| Database | MySQL 8.0.16+ (CHECK constraints are enforced from this version) |
| Auth | Laravel Sanctum (bearer tokens) |
| API docs | dedoc/scramble (OpenAPI 3) |

## Requirements

- PHP 8.2 or higher with the `pdo_mysql` extension
- Composer
- MySQL 8.0.16 or higher

## Setup

```bash
# 1. Clone and install dependencies
git clone <your-repo-url> equipment-booking
cd equipment-booking
composer install

# 2. Create your environment file and app key
cp .env.example .env
php artisan key:generate

# 3. Create the database (in MySQL / phpMyAdmin)
#    CREATE DATABASE equipment_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Set DB_USERNAME and DB_PASSWORD in .env, then migrate and seed
php artisan migrate --seed

# 5. Start the server
php artisan serve
```

The API is served at `http://127.0.0.1:8000/api`.

## Environment configuration

Key variables in `.env` (see `.env.example` for the full list):

| Variable | Purpose | Example |
|---|---|---|
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` / `DB_PORT` | MySQL host and port | `127.0.0.1` / `3306` |
| `DB_DATABASE` | Database name | `equipment_booking` |
| `DB_USERNAME` / `DB_PASSWORD` | MySQL credentials | |
| `APP_URL` | Base URL, used by the API docs | `http://localhost:8000` |
| `SESSION_DRIVER` | Not used by a token API | `file` |
| `CACHE_STORE` | Used by the login rate limiter | `file` |
| `QUEUE_CONNECTION` | No queued jobs in this project | `sync` |

## Seeded accounts

`php artisan migrate --seed` creates these users (password for all: `password`):

| Role | Email |
|---|---|
| Admin | admin@example.com |
| Resource Manager | manager@example.com |
| Employee | employee@example.com |

It also creates sample categories and resources, including 5 projectors, which matches the example in the brief. Change these credentials before any real deployment.

Public registration always creates an **Employee**. Roles can only be changed by an admin.

## API documentation

With the server running, open the interactive OpenAPI docs at:

```
http://127.0.0.1:8000/docs/api
```

The raw OpenAPI JSON is available at `/docs/api.json`, which can be imported into Postman or Insomnia.

## Authentication

1. `POST /api/login` with `email` and `password`. The response contains a `token`.
2. Send it on every protected request:

```
Authorization: Bearer <token>
Accept: application/json
```

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

Always send `Accept: application/json`. Login is rate limited to 10 attempts per minute.

## Roles and permissions

| Action | Employee | Resource Manager | Admin |
|---|:---:|:---:|:---:|
| Register, log in, view/update own profile | Yes | Yes | Yes |
| Browse categories and active resources | Yes | Yes | Yes |
| See inactive resources | No | Yes | Yes |
| Create, update, delete categories and resources | No | Yes | Yes |
| Create booking requests, view own bookings | Yes | Yes | Yes |
| Approve / reject bookings, view all bookings | No | Yes | Yes |
| Manage users and roles | No | No | Yes |

Admins pass every role check.

## Endpoints

### Auth and profile

| Method | Path | Description |
|---|---|---|
| POST | `/api/register` | Create an employee account |
| POST | `/api/login` | Get an access token |
| POST | `/api/logout` | Revoke the current token |
| GET | `/api/me` | View own profile |
| PUT/PATCH | `/api/me` | Update name, email or password |

### Categories

| Method | Path | Access |
|---|---|---|
| GET | `/api/categories`, `/api/categories/{id}` | Any user |
| POST | `/api/categories` | Manager / Admin |
| PUT/PATCH | `/api/categories/{id}` | Manager / Admin |
| DELETE | `/api/categories/{id}` | Manager / Admin (409 if it still has resources) |

### Resources

| Method | Path | Access |
|---|---|---|
| GET | `/api/resources` | Any user (paginated, filterable) |
| GET | `/api/resources/{id}` | Any user |
| POST | `/api/resources` | Manager / Admin |
| PUT/PATCH | `/api/resources/{id}` | Manager / Admin |
| DELETE | `/api/resources/{id}` | Manager / Admin (409 if it has booking history; deactivate instead) |

**`GET /api/resources` query parameters**

| Parameter | Description |
|---|---|
| `category_id` | Filter by category |
| `location` | Exact location match |
| `q` | Keyword search on name and description |
| `available_from`, `available_to` | Only resources with free stock for the whole range |
| `min_quantity` | With a date range, require at least this many units free (default 1) |
| `is_active` | Managers and admins only |
| `per_page` | Page size, 1 to 100 (default 15) |

Each resource includes a computed `available_quantity`: units free for the requested range, or for today if no range is given.

### Bookings

> In progress. These endpoints are added in the next build steps.

| Method | Path | Access |
|---|---|---|
| POST | `/api/bookings` | Any user |
| GET | `/api/bookings` | Employees see their own; managers and admins see all (filter by status, user, resource, start date, end date) |
| GET | `/api/bookings/{id}` | Owner, manager, admin |
| POST | `/api/bookings/{id}/cancel` | Owner (pending or approved) |
| POST | `/api/bookings/{id}/approve` | Manager / Admin |
| POST | `/api/bookings/{id}/reject` | Manager / Admin (reason required) |

## Database design

| Table | Purpose | Key constraints |
|---|---|---|
| `users` | Accounts and roles | Unique `email`; `role` is one of admin, resource_manager, employee |
| `categories` | Groups resources | Unique `name` |
| `resources` | Equipment and inventory | FK `category_id` (restrict delete); `total_quantity` unsigned; indexes on category/active and location |
| `bookings` | Booking requests | FK `user_id`; `reviewed_by` FK to users (nullable); CHECK `end_date >= start_date`; index on `(status, start_date, end_date)` |
| `booking_items` | Resources and quantities in a booking | FK `booking_id` (cascade), FK `resource_id` (restrict); unique `(booking_id, resource_id)`; CHECK `quantity > 0` |

Relationships:

- A category has many resources.
- A user has many bookings.
- A booking has many booking items, and each item belongs to one resource.
- A booking optionally belongs to a reviewing user (`reviewed_by`).

## Business rules

- **Available quantity is computed, never stored.** Availability depends on the date range, so a stored value would drift out of sync.
- **Only approved bookings reserve stock.** Pending requests do not.
- **Peak-usage check.** For a requested range, usage is summed per day and the busiest day is used. A resource with 5 units, 4 booked on 10-11 and 4 booked on 12-13, still has 1 unit free for 10-13.
- **Dates are whole days with an inclusive end date.** A booking from 10 to 12 occupies the 10th, 11th and 12th.
- **Inactive resources** are hidden from employees and cannot be booked.
- **A resource's total quantity cannot be lowered** below the peak already committed to approved bookings.
- **Resources with booking history cannot be deleted**; deactivate them instead.
- **Approval is re-checked inside a database transaction** with row locks, so two concurrent approvals cannot oversell stock.

## Security notes

- Passwords are hashed; tokens are stored hashed by Sanctum.
- API Resources whitelist the fields returned, so passwords and tokens never appear in responses.
- `role`, `status` and ownership fields are not mass-assignable, so clients cannot set them.
- Login failures return one generic message, so registered emails cannot be discovered.

## Testing

Tests run against MySQL, not SQLite, because the availability logic relies on row locking and date handling that SQLite does not reproduce faithfully. `phpunit.xml` points at the same database as `.env`, and tests use database transactions so seeded data is left intact.

```bash
php artisan test
```

> Test suites are added in a later build step, including the projector double-booking scenario from the brief.

## Project structure

```
app/
  Enums/            Role, BookingStatus
  Http/
    Controllers/    Auth, Profile, Category, Resource (Booking coming)
    Middleware/     EnsureUserHasRole
    Resources/      API response shapes
  Models/           User, Category, Resource, Booking, BookingItem
  Services/         AvailabilityService (availability and peak-usage logic)
database/
  migrations/  factories/  seeders/
routes/api.php
```

## License

MIT