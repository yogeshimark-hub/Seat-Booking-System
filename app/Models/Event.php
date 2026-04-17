<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    protected $fillable = [
          'event_name',
          'event_venue',
          'event_date',
          'total_rows',
          'total_columns',
      ];



    //   an event has all seats for that event 

      public function seats(){
        return $this->hasMany(Seat::class);
      }

    //   an event can have multiple booking at a time 
    
      public function bookings(){
        return $this->hasMany(Booking::class);
      }
}
