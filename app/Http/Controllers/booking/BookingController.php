<?php

namespace App\Http\Controllers\booking;

use App\Http\Controllers\Controller;
use App\Services\SeatBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;


  class BookingController extends Controller
  {
      protected $seatBookingService;

      public function __construct(SeatBookingService $seatBookingService)
      {
          $this->seatBookingService = $seatBookingService;
      }

      public function initiate(Request $request)
      {
          $request->validate([
              'event_id' => 'required|exists:events,id',
              'seat_ids' => 'required|string',
          ]);

          $seatIds = array_map('intval', explode(',', $request->seat_ids));
          $seatIds = array_filter($seatIds);

          if (empty($seatIds)) {
              return back()->with('error', 'Please select at least one seat.');
          }

          // Guest: save to session and redirect to login
          if (!Auth::check()) {
              session([
                  'pending_booking' => [
                      'event_id' => $request->event_id,
                      'seat_ids' => $seatIds,
                  ]
              ]);

              return redirect()
                  ->route('login')
                  ->with('error', 'Please login to continue booking your seats.');
          }

          // Logged-in: attempt to lock the seats
          $result = $this->seatBookingService->lockSeats(
              (int) $request->event_id,
              $seatIds
          );

          if (!$result['success']) {
              return redirect()
                  ->route('events.show', $request->event_id)
                  ->with('error', $result['message']);
          }

          return redirect()
              ->route('booking.confirm', $request->event_id)
              ->with('success', 'Seats locked! Please complete booking within ' .
                config('booking.lock_duration_minutes', 5) . ' minutes.');
      }

 public function showConfirmation(Event $event)
  {
      $userId = Auth::id();

      $lockedSeats = $this->seatBookingService->getLockedSeatsForUser($event->id, $userId);

      if ($lockedSeats->isEmpty()) {
          return redirect()
              ->route('events.show', $event->id)
              ->with('error', 'No active locked seats. Please select seats again.');
      }

      // Find the nearest expiry time (they should all be the same, but let's be safe)
      $expiresAt = $lockedSeats->min('lock_expires_at');

      return view('booking.confirm', [
          'event' => $event,
          'seats' => $lockedSeats,
          'expiresAt' => $expiresAt,
      ]);
  }

  public function confirmBooking(Event $event)
  {
      $userId = Auth::id();

      $result = $this->seatBookingService->confirmBooking($event->id, $userId);

      if (!$result['success']) {
          return redirect()
              ->route('events.show', $event->id)
              ->with('error', $result['message']);
      }

      return redirect()
          ->route('events.show', $event->id)
          ->with('success', 'Booking confirmed! ' . $result['seat_count'] . ' seat(s) booked successfully.');
  }

  public function cancel(Event $event)
  {
      $userId = Auth::id();

      $this->seatBookingService->releaseUserLocks($event->id, $userId);

      return redirect()
          ->route('events.show', $event->id)
          ->with('success', 'Your seat selection has been cancelled.');
  }

  }