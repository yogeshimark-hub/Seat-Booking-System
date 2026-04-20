# Seat Booking System

Real-time seat booking system built with Laravel 12. Handles concurrent seat locks, atomic bookings, auto-release of expired holds, and **cross-browser live seat status sync** via AJAX polling. Supports two separate auth guards — regular users and admins/vendors.

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
| POST | `/seats/{seat}/lock` | auth:web,admin | AJAX: lock one seat on click |
| DELETE | `/seats/{seat}/unlock` | auth:web,admin | AJAX: release one seat (deselect) |
| GET | `/events/{event}/seat-statuses` | — | JSON feed polled every 5s for live status |

## Database

Core tables:

- `users` — regular end-users
- `admins` — admins & vendors (with `role` enum)
- `events` — event details + `total_rows` / `total_columns`
- `seats` — per-event seat grid; `locked_by` + `locked_by_type` record holder
- `bookings` — one row per booked seat; `user_id` + `booker_type` record buyer

FKs from `seats.locked_by` and `bookings.user_id` were dropped intentionally so the ID column can reference either `users` or `admins`; the `*_type` column disambiguates.

## Cross-browser real-time seat sync

**Problem solved:** previously, a seat selected in one browser (Edge) didn't show as locked in another browser (Chrome / Incognito) until the user hit "Book". Selection lived only in the client-side JS — the DB was never told.

**Solution:** every seat click on the event page now fires an AJAX call that writes the lock to the database, and every browser polls the seat-status endpoint every 5 seconds to mirror the truth.

### Flow

1. **Logged-in user clicks a seat** → `POST /seats/{id}/lock` → `SeatBookingService::lockSeats()` wraps the update in a `DB::transaction` with `lockForUpdate()` → DB row becomes `status=locked`, `locked_by=user`, `lock_expires_at=now+5min` → JSON response turns the seat yellow in the UI.
2. **Same user clicks again to deselect** → `DELETE /seats/{id}/unlock` → `SeatBookingService::releaseSingleSeatLock()` verifies ownership (user can only release their own lock) and frees the seat.
3. **Every 5 seconds, every open browser** hits `GET /events/{id}/seat-statuses` → server returns `[{id, status, mine}]` for every seat → JS updates CSS classes so locks made elsewhere appear here without a manual reload.
4. **On initial page load**, Blade renders the current user's own locks as `selected` (yellow) instead of `locked` (gray) by checking `locked_by` against the current booker.

### Why guests don't write to the DB

The click handler skips the AJAX call when the user is a guest — selection stays in JS memory until they hit "Book Selected Seats →", which redirects them to login and restores the selection via the `restore_seats` session flow. This is a deliberate abuse-prevention measure: if guests could lock seats, any anonymous visitor could hold every seat for 5 minutes and deny service to real users. Polling still runs for guests so they see other users' locks, but a poll result of `available` never wipes a guest's locally-selected seat.

### Key files

- `app/Services/SeatBookingService.php` — `releaseSingleSeatLock()` for per-seat unlocks.
- `app/Http/Controllers/booking/BookingController.php` — `lockSeat()`, `unlockSeat()`, `seatStatuses()` return JSON for AJAX callers.
- `resources/views/events/show.blade.php` — click handler + 5s polling loop.
- `resources/views/layouts/app.blade.php` — `<meta name="csrf-token">` for AJAX CSRF protection.

## Event date validation

Admins/vendors cannot create events in the past. Defense is layered at three levels:

| Layer | File | Rule |
|---|---|---|
| HTML picker | `resources/views/events/create.blade.php` | `min="{{ now()->format('Y-m-d\TH:i') }}"` disables past dates in the calendar UI |
| Client JS | `public/js/event-form-validation.js` | `new Date(val) <= new Date()` shows inline error |
| Server | `app/Http/Requests/StoreEventRequests.php` | `'event_date' => 'required\|date\|after:now'` final gate |

