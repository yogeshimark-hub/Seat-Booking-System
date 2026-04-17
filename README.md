# Seat Booking System

Real-time seat booking system built with Laravel 12. Handles concurrent seat locks, atomic bookings, and auto-release of expired holds. Supports two separate auth guards — regular users and admins/vendors.

## Stack

- Laravel 12, PHP 8.2+
- MySQL
- Blade + jQuery + custom CSS
- Laravel Scheduler for background cleanup

## Setup

```bash
git clone <repo-url>
cd Seat-Booking-System
composer install
cp .env.example .env
php artisan key:generate
```

Update DB credentials in `.env`, then:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

For expired-lock cleanup (dev), run in a separate terminal:

```bash
php artisan schedule:work
```

On production, add this cron entry:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Config

Lock duration is configurable via `.env`:

```
SEAT_LOCK_DURATION=5
```

## Authentication & Roles

The app uses **two separate guards** backed by two separate tables:

| Guard | Table | Who | Login URL |
|-------|-------|-----|-----------|
| `web` | `users` | Regular users (book seats only) | `/login` |
| `admin` | `admins` | Admins & vendors (manage events + book seats) | `/admin/login` |

Seeded credentials (via `php artisan db:seed`):

| Email | Password | Role |
|-------|----------|------|
| `admin@example.com` | `admin123` | admin |
| `vendor@example.com` | `vendor123` | vendor |

### Authorization rules

- **Guests** — browse events, view seat layouts
- **Users** (`web` guard) — register/login, lock & book seats
- **Admins / Vendors** (`admin` guard) — create/edit/delete events, and also book seats

Event create/edit/update/destroy is locked behind `auth:admin`. Booking routes accept either guard (`auth:web,admin`).

### Booker tracking

Because bookings can now come from two different tables, `bookings.user_id` + `booker_type` (and `seats.locked_by` + `locked_by_type`) together identify who locked/booked a seat. The `App\Support\Booker` helper returns the active booker from whichever guard is logged in.

## Architecture

- **Controllers** — thin, delegate to services
- **Services** — business logic (`EventService`, `SeatBookingService`)
- **Form Requests** — validation (`StoreEventRequests`, `LoginRequest`, `RegisterRequest`)
- **Cache** — seat availability cached, invalidated on writes
- **Concurrency** — `DB::transaction` + `lockForUpdate()` (pessimistic row locking) prevents double booking
- **Background job** — `seats:release-expired` Artisan command runs every minute via scheduler
- **Client validation** — jQuery validators (`public/js/*-form-validation.js`) mirror server rules for instant feedback
- **Post-logout back-button guard** — `NoCache` middleware sends `no-store` headers on authenticated pages

## Route files

Routes are split by audience:

- **`routes/web.php`** — public pages, user auth (`/login`, `/register`), booking confirm/cancel
- **`routes/admin.php`** — admin/vendor auth (`/admin/login`, `/admin/logout`) and event CRUD

`routes/admin.php` is auto-loaded from `bootstrap/app.php` under the `web` middleware group.

## Key routes

| Method | URI | Guard | Purpose |
|--------|-----|-------|---------|
| GET | `/` | — | Event list |
| GET | `/events/{id}` | — | Seat layout |
| GET | `/register`, `/login` | guest:web | User auth forms |
| POST | `/logout` | auth:web | User logout |
| GET | `/admin/login` | guest:admin | Admin/vendor login |
| POST | `/admin/logout` | auth:admin | Admin/vendor logout |
| GET | `/events/create` | auth:admin | Create-event form |
| POST | `/events` | auth:admin | Store event + seats |
| PUT/DELETE | `/events/{id}` | auth:admin | Update / delete event |
| POST | `/booking/initiate` | — | Lock selected seats (redirects guests to login) |
| GET | `/booking/confirm/{event}` | auth:web,admin | Confirmation page with countdown |
| POST | `/booking/confirm/{event}` | auth:web,admin | Finalize booking |
| POST | `/booking/cancel/{event}` | auth:web,admin | Release held seats |

## Database

Core tables:

- `users` — regular end-users
- `admins` — admins & vendors (with `role` enum)
- `events` — event details + `total_rows` / `total_columns`
- `seats` — per-event seat grid; `locked_by` + `locked_by_type` record holder
- `bookings` — one row per booked seat; `user_id` + `booker_type` record buyer

FKs from `seats.locked_by` and `bookings.user_id` were dropped intentionally so the ID column can reference either `users` or `admins`; the `*_type` column disambiguates.
