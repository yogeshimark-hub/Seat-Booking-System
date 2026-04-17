# Real-Time Seat Booking System - Flowchart

## 1. Main User Booking Flow

```
                        ┌─────────────────┐
                        │      START      │
                        └────────┬────────┘
                                 │
                                 ▼
                        ┌─────────────────┐
                        │  User Logs In / │
                        │  Opens System   │
                        └────────┬────────┘
                                 │
                                 ▼
                        ┌─────────────────┐
                        │  Select Event / │
                        │     Show        │
                        └────────┬────────┘
                                 │
                                 ▼
                  ┌──────────────────────────────┐
                  │  Fetch Seat Layout for Event  │
                  │  (row, column, status)        │
                  └──────────────┬───────────────┘
                                 │
                                 ▼
                  ┌──────────────────────────────┐
                  │  Display Seat Map to User     │
                  │  (available / locked / booked) │
                  └──────────────┬───────────────┘
                                 │
                                 ▼
                  ┌──────────────────────────────┐
                  │  User Selects Seat(s)         │
                  └──────────────┬───────────────┘
                                 │
                                 ▼
                  ┌──────────────────────────────┐
                  │  Acquire Lock on Seat(s)      │
                  │  (DB Transaction + Row Lock)  │
                  └──────────────┬───────────────┘
                                 │
                        ┌────────┴────────┐
                        ▼                 ▼
              ┌──────────────┐   ┌────────────────┐
              │ Seat Status  │   │  Seat Status   │
              │ = available? │   │ = locked/booked│
              └──────┬───────┘   └───────┬────────┘
                     │ YES               │ NO
                     ▼                   ▼
          ┌────────────────────┐  ┌─────────────────────┐
          │ Lock Seat(s)       │  │ Return Error:       │
          │ status → "locked"  │  │ "Seat unavailable"  │
          │ Set lock_expiry    │  │ Refresh seat map    │
          │ (e.g. +5 min)     │  └─────────┬───────────┘
          │ Store user_id      │            │
          └────────┬───────────┘            │
                   │                        ▼
                   │               ┌────────────────┐
                   │               │ User selects   │
                   │               │ different seat │──────┐
                   │               └────────────────┘      │
                   │                                       │
                   ▼                    (loops back to     │
          ┌────────────────────┐        seat selection)    │
          │ Start Lock Timer   │◄──────────────────────────┘
          │ (configurable,     │
          │  e.g. 5 minutes)   │
          └────────┬───────────┘
                   │
                   ▼
          ┌────────────────────┐
          │ Show Booking       │
          │ Confirmation Page  │
          │ (with countdown)   │
          └────────┬───────────┘
                   │
          ┌────────┴──────────────────┐
          ▼                           ▼
 ┌─────────────────┐       ┌──────────────────┐
 │ User Confirms   │       │ User Does NOT    │
 │ Booking         │       │ Confirm (timeout │
 │ (within timer)  │       │  or cancels)     │
 └────────┬────────┘       └────────┬─────────┘
          │                         │
          ▼                         ▼
 ┌─────────────────────┐  ┌──────────────────────┐
 │ Check: Is lock      │  │ Release Lock         │
 │ still valid?        │  │ status → "available" │
 │ (not expired)       │  │ Clear user_id        │
 └───┬─────────┬───────┘  └──────────┬───────────┘
     │YES      │NO                   │
     ▼         ▼                     ▼
┌──────────┐ ┌───────────────┐  ┌──────────────┐
│ BEGIN DB │ │ Error: Lock   │  │ Seat becomes │
│TRANSACTION│ │ expired,     │  │ available for│
│          │ │ seat released │  │ other users  │
└────┬─────┘ └───────┬───────┘  └──────────────┘
     │               │
     ▼               ▼
┌──────────────┐  ┌───────────────┐
│ Update seat  │  │ Redirect to   │
│ status →     │  │ seat selection│
│ "booked"     │  └───────────────┘
│ Record       │
│ booking info │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│   COMMIT     │
│ TRANSACTION  │
│ (atomic)     │
└──────┬───────┘
       │
       ▼
┌──────────────────┐
│ Booking Success! │
│ Show confirmation│
│ details to user  │
└──────────────────┘
```

---

## 2. Background Process Flow (Auto-Release Expired Locks)

```
┌──────────────────────────┐
│  Background Job / Cron   │
│  (runs every 1 minute)   │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│ Query: Find all seats    │
│ WHERE status = "locked"  │
│ AND lock_expiry < NOW()  │
└────────────┬─────────────┘
             │
     ┌───────┴───────┐
     ▼               ▼
┌──────────┐   ┌───────────┐
│ Found    │   │ None      │
│ expired  │   │ found     │
│ locks    │   └─────┬─────┘
└────┬─────┘         │
     │               ▼
     │          ┌─────────┐
     │          │  Sleep   │
     │          │  & Retry │
     │          └─────────┘
     ▼
┌──────────────────────────┐
│ Release each expired     │
│ seat:                    │
│  status → "available"    │
│  Clear user_id           │
│  Clear lock_expiry       │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│ Log release event        │
│ (for audit/debugging)    │
└──────────────────────────┘
```

---

## 3. Concurrency Handling Flow (Same Seat, 2 Users)

```
  User A                              User B
    │                                    │
    ▼                                    ▼
┌──────────┐                      ┌──────────┐
│ Selects  │                      │ Selects  │
│ Seat #5  │                      │ Seat #5  │
└────┬─────┘                      └────┬─────┘
     │                                 │
     ▼                                 ▼
┌──────────────┐               ┌──────────────┐
│ Acquire DB   │               │ Acquire DB   │
│ Row Lock on  │               │ Row Lock on  │
│ Seat #5      │               │ Seat #5      │
└──────┬───────┘               └──────┬───────┘
       │                              │
       ▼                              │ (WAITS - blocked
┌──────────────┐                      │  by User A's lock)
│ Lock granted │                      │
│ Seat → locked│                      │
│ for User A   │                      │
└──────┬───────┘                      │
       │                              │
       ▼                              │
┌──────────────┐                      │
│ COMMIT /     │                      │
│ Release DB   │──────────────────────┘
│ Row Lock     │                      │
└──────────────┘                      ▼
                              ┌──────────────────┐
                              │ Lock granted but  │
                              │ Seat status =     │
                              │ "locked" (by A)   │
                              └────────┬──────────┘
                                       │
                                       ▼
                              ┌──────────────────┐
                              │ REJECT: Return   │
                              │ "Seat unavailable"│
                              │ to User B        │
                              └──────────────────┘
```

---

## 4. Seat State Machine

```
                 ┌───────────┐
        ┌────────│ AVAILABLE │◄──────────────┐
        │        └───────────┘               │
        │ User selects              Timeout expires /
        │ seat                      User cancels /
        │                          Lock auto-released
        ▼                                    │
   ┌──────────┐                              │
   │  LOCKED  │──────────────────────────────┘
   └────┬─────┘
        │ User confirms
        │ booking (within timer)
        ▼
   ┌──────────┐
   │  BOOKED  │  (Final state - no reversal in this flow)
   └──────────┘
```

### State Transitions Summary

| From      | To        | Trigger                                  |
|-----------|-----------|------------------------------------------|
| Available | Locked    | User selects seat                        |
| Locked    | Available | Lock timeout expires / User cancels      |
| Locked    | Booked    | User confirms booking within lock period |

---

## 5. API Endpoint Flow Summary

```
┌─────────────────────────────────────────────────────────────┐
│                      API ENDPOINTS                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  GET  /events/{id}/seats        → Fetch seat layout & map   │
│           │                                                 │
│           ▼                                                 │
│  POST /seats/lock               → Lock selected seat(s)     │
│           │                                                 │
│           ▼                                                 │
│  POST /bookings/confirm         → Confirm booking           │
│           │                                                 │
│           ▼                                                 │
│  POST /seats/release            → Cancel / release seat(s)  │
│                                                             │
│  ─── Background ───                                         │
│  CRON  Release expired locks    → Auto-release stale locks  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Database Schema Overview

```
┌──────────────────┐       ┌──────────────────┐
│     events       │       │      users       │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │       │ id (PK)          │
│ name             │       │ name             │
│ venue            │       │ email            │
│ date             │       │ password         │
└────────┬─────────┘       └────────┬─────────┘
         │ 1:N                      │ 1:N
         ▼                          ▼
┌─────────────────────────────────────────────┐
│                   seats                      │
├─────────────────────────────────────────────┤
│ id (PK)                                      │
│ event_id (FK → events)                       │
│ row                                          │
│ column                                       │
│ status (available / locked / booked)         │
│ locked_by (FK → users, nullable)             │
│ locked_at (timestamp, nullable)              │
│ lock_expires_at (timestamp, nullable)        │
└──────────────────┬──────────────────────────┘
                   │ 1:1
                   ▼
┌─────────────────────────────────────────────┐
│                 bookings                     │
├─────────────────────────────────────────────┤
│ id (PK)                                      │
│ seat_id (FK → seats)                         │
│ user_id (FK → users)                         │
│ event_id (FK → events)                       │
│ booked_at (timestamp)                        │
└─────────────────────────────────────────────┘
```

---

## 7. Edge Cases Handled

```
┌────────────────────────────────────┐
│         EDGE CASE                  │        HANDLING
├────────────────────────────────────┤─────────────────────────────────
│ Two users select same seat         │ → DB row lock, first wins
│ Lock expires during confirmation   │ → Reject booking, re-select
│ System crash with active locks     │ → Background job cleans up
│ User closes browser while locked   │ → Auto-release after timeout
│ Double-click / duplicate request   │ → Idempotency check on lock
└────────────────────────────────────┘
```
