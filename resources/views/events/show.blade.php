@extends('layouts.app')

  @section('title', $event->name)

  @section('content')

      <a href="{{ route('home') }}" class="btn btn-primary">← Back to Events</a>

      <div class="event-header">
          <h1>{{ $event->name }}</h1>
          <p><strong>Venue:</strong> {{ $event->event_venue }}</p>
          <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($event->event_date)->format('d M Y, h:i A') }}</p>
      </div>

      <div class="screen">SCREEN THIS WAY</div>

      <div class="seat-legend">
          <div><span class="seat-sample available"></span> Available</div>
          <div><span class="seat-sample locked"></span> Locked</div>
          <div><span class="seat-sample booked"></span> Booked</div>
      </div>

      <div class="seat-grid" style="grid-template-columns: 40px repeat({{ $event->total_columns }}, 40px);">

          {{-- Top row: column numbers --}}
          <div class="seat-label"></div>
          @for ($c = 1; $c <= $event->total_columns; $c++)
              <div class="seat-label">{{ $c }}</div>
          @endfor

          {{-- Group seats by row --}}
          @php
              $seatsByRow = $event->seats->groupBy('seat_row');
          @endphp

          @foreach ($seatsByRow as $rowLetter => $rowSeats)
              {{-- Row label (A, B, C...) --}}
              <div class="seat-label">{{ $rowLetter }}</div>

              {{-- Seats in this row --}}
              @foreach ($rowSeats as $seat)
                  <div class="seat {{ $seat->status }}"
                       data-seat-id="{{ $seat->id }}"
                       title="Seat {{ $seat->seat_row }}{{ $seat->seat_column }} - {{ ucfirst($seat->status) }}">
                      {{ $seat->seat_column }}
                  </div>
              @endforeach
          @endforeach

      </div>
      <form id="booking-form" action="{{ route('booking.initiate') }}" method="POST">
      @csrf
      <input type="hidden" name="event_id" value="{{ $event->id }}">
      <input type="hidden" name="seat_ids" id="seat-ids-input" value="">

      <div id="booking-bar" class="booking-bar" style="display:none;">
          <span>Selected: <span id="selected-count">0</span> seat(s)</span>
          <button type="submit" class="btn btn-success">Book Selected Seats →</button>
      </div>
  </form>

  @endsection

@push('scripts')
  <script>
  $(document).ready(function () {
      let selectedSeats = [];

      function updateBookingBar() {
          $('#selected-count').text(selectedSeats.length);
          $('#seat-ids-input').val(selectedSeats.join(','));
          $('#booking-bar').toggle(selectedSeats.length > 0);
      }

      // Click on available seats only
      $('.seat.available').on('click', function () {
          const seatId = parseInt($(this).data('seat-id'));

          if (selectedSeats.includes(seatId)) {
              selectedSeats = selectedSeats.filter(id => id !== seatId);
              $(this).removeClass('selected');
          } else {
              selectedSeats.push(seatId);
              $(this).addClass('selected');
          }

          updateBookingBar();
      });

      // Restore selection after login redirect
      @if(session('restore_seats'))
          const restoreIds = "{{ session('restore_seats') }}".split(',').map(Number);
          let unavailableCount = 0;

          restoreIds.forEach(function (seatId) {
              const $seat = $('.seat[data-seat-id="' + seatId + '"]');

              if ($seat.hasClass('available')) {
                  selectedSeats.push(seatId);
                  $seat.addClass('selected');
              } else {
                  unavailableCount++;
              }
          });

          updateBookingBar();

          if (unavailableCount > 0) {
              alert(unavailableCount + ' of your previously selected seat(s) are no longer available.');
          }
      @endif
  });
  </script>
  @endpush