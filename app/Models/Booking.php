<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $fillable = [
          'user_id',
          'seat_id',
          'event_id',
          'booked_at',
    ];
   


    // this booking belog to which user
     public function user()
      {
          return $this->belongsTo(User::class);
      }

    //   this booking tell the seat to that user
      public function seat()
      {
          return $this->belongsTo(Seat::class);
      }

    //   here tell that this booking belog to which event
      public function event()
      {
          return $this->belongsTo(Event::class);
      }
}
