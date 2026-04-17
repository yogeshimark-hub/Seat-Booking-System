<?php

  namespace App\Services;

  use App\Models\Seat;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Cache;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Carbon;
  use App\Models\Booking;

  class SeatBookingService
  {
      public function lockSeats(int $eventId, array $seatIds): array
      {
          return DB::transaction(function () use ($eventId, $seatIds) {

              // Lock these seat rows so no other transaction can touch them
              $seats = Seat::whereIn('id', $seatIds)
                  ->where('event_id', $eventId)
                  ->lockForUpdate()
                  ->get();

              // Safety: the count must match what the user selected
              if ($seats->count() !== count($seatIds)) {
                  return [
                      'success' => false,
                      'message' => 'One or more selected seats do not belong to this event.',
                  ];
              }

              // Check each seat: it must be available OR already locked by the same user (re-selecting)
              $unavailable = [];
              $userId = Auth::id();

              foreach ($seats as $seat) {
                  $isAvailable = $seat->status === 'available';
                  $isOwnedByMe = $seat->status === 'locked'
                      && $seat->locked_by === $userId
                      && $seat->lock_expires_at
                      && Carbon::parse($seat->lock_expires_at)->isFuture();

                  if (!$isAvailable && !$isOwnedByMe) {
                      $unavailable[] = $seat->seat_row . $seat->seat_column;
                  }
              }

              if (!empty($unavailable)) {
                  return [
                      'success' => false,
                      'message' => 'These seats are no longer available: ' . implode(', ', $unavailable),
                  ];
              }

              // All good — lock them
              $lockDuration = config('booking.lock_duration_minutes', 5);
              $expiresAt = now()->addMinutes($lockDuration);

              Seat::whereIn('id', $seatIds)->update([
                  'status' => 'locked',
                  'locked_by' => $userId,
                  'lock_expires_at' => $expiresAt,
              ]);

              // Clear the seat cache for this event
              Cache::forget("event.{$eventId}.seats");

              return [
                  'success' => true,
                  'expires_at' => $expiresAt,
                  'seat_ids' => $seatIds,
              ];
          });
      }

       public function getLockedSeatsForUser(int $eventId, int $userId)
  {
      return Seat::where('event_id', $eventId)
          ->where('locked_by', $userId)
          ->where('status', 'locked')
          ->where('lock_expires_at', '>', now())
          ->orderBy('seat_row')
          ->orderBy('seat_column')
          ->get();
  }

  public function confirmBooking(int $eventId, int $userId): array
  {
      return DB::transaction(function () use ($eventId, $userId) {

          // Lock these rows to prevent race conditions
          $seats = Seat::where('event_id', $eventId)
              ->where('locked_by', $userId)
              ->where('status', 'locked')
              ->lockForUpdate()
              ->get();

          if ($seats->isEmpty()) {
              return [
                  'success' => false,
                  'message' => 'No locked seats found. Please select seats again.',
              ];
          }

          // Re-verify each seat is still within lock period
          foreach ($seats as $seat) {
              if (!$seat->lock_expires_at || Carbon::parse($seat->lock_expires_at)->isPast()) {
                  return [
                      'success' => false,
                      'message' => 'Your lock has expired. Please select seats again.',
                  ];
              }
          }

          // Update seats to booked
          $seatIds = $seats->pluck('id')->toArray();

          Seat::whereIn('id', $seatIds)->update([
              'status' => 'booked',
              'lock_expires_at' => null,
          ]);

          // Create booking records (one per seat)
          $bookingData = [];
          foreach ($seats as $seat) {
              $bookingData[] = [
                  'user_id' => $userId,
                  'seat_id' => $seat->id,
                  'event_id' => $eventId,
                  'booked_at' => now(),
                  'created_at' => now(),
                  'updated_at' => now(),
              ];
          }

          Booking::insert($bookingData);

          Cache::forget("event.{$eventId}.seats");

          return [
              'success' => true,
              'seat_count' => $seats->count(),
          ];
      });
  }


  public function releaseUserLocks(int $eventId, int $userId): int
  {
      $affected = Seat::where('event_id', $eventId)
          ->where('locked_by', $userId)
          ->where('status', 'locked')
          ->update([
              'status' => 'available',
              'locked_by' => null,
              'lock_expires_at' => null,
          ]);

      Cache::forget("event.{$eventId}.seats");

      return $affected;
  }
  public function releaseExpiredLocks(): int
  {
      $expiredSeats = Seat::where('status', 'locked')
          ->where('lock_expires_at', '<=', now())
          ->get();

      if ($expiredSeats->isEmpty()) {
          return 0;
      }

      // Group by event so we only clear each event's cache once
      $eventIds = $expiredSeats->pluck('event_id')->unique();

      $affected = Seat::where('status', 'locked')
          ->where('lock_expires_at', '<=', now())
          ->update([
              'status' => 'available',
              'locked_by' => null,
              'lock_expires_at' => null,
          ]);

      // Clear cache for each affected event
      foreach ($eventIds as $eventId) {
          Cache::forget("event.{$eventId}.seats");
      }

      return $affected;
  }
  }