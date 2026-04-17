<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
     protected $table = 'seats';
     protected $fillable = [
          'event_id',
          'seat_row',
          'seat_column',
          'status',
          'locked_by',
          'locked_by_type',
          'lock_expires_at',
      ];


    //   this tell that this seat belong to which event
      public function event()
      {
          return $this->belongsTo(Event::class);
      }

    //   tell use this seat booked by which users its temporary untill final that seat within time period of 5 mint
      public function lockedByUser()
      {
          return $this->belongsTo(User::class, 'locked_by');
      }

    //   seat can have only one booking 
      public function booking()
      {
          return $this->hasOne(Booking::class);
      }
}
