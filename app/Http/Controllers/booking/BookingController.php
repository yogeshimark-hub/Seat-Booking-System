<?php

namespace App\Http\Controllers\booking;

use App\Http\Controllers\Controller;
use App\Services\SeatBookingService;
use App\Support\Booker;
use Illuminate\Http\Request;
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

        $booker = Booker::current();

        // Guest: save to session and redirect to login
        if (!$booker) {
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

        $result = $this->seatBookingService->lockSeats(
            (int) $request->event_id,
            $seatIds,
            $booker['id'],
            $booker['type']
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
        $booker = Booker::current();

        $lockedSeats = $this->seatBookingService->getLockedSeatsForUser(
            $event->id,
            $booker['id'],
            $booker['type']
        );

        if ($lockedSeats->isEmpty()) {
            return redirect()
                ->route('events.show', $event->id)
                ->with('error', 'No active locked seats. Please select seats again.');
        }

        $expiresAt = $lockedSeats->min('lock_expires_at');

        return view('booking.confirm', [
            'event' => $event,
            'seats' => $lockedSeats,
            'expiresAt' => $expiresAt,
        ]);
    }

    public function confirmBooking(Event $event)
    {
        $booker = Booker::current();

        $result = $this->seatBookingService->confirmBooking(
            $event->id,
            $booker['id'],
            $booker['type']
        );

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
        $booker = Booker::current();

        $this->seatBookingService->releaseUserLocks(
            $event->id,
            $booker['id'],
            $booker['type']
        );

        return redirect()
            ->route('events.show', $event->id)
            ->with('success', 'Your seat selection has been cancelled.');
    }
}
