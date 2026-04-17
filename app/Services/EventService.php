<?php
namespace App\Services;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class EventService{
 
public function getAllEvents()
{
    
    return Cache::remember('events.all', 600, function(){
        return Event::all();
    });

}

  public function createEventWithSeats(array $data)
      {
          return DB::transaction(function () use ($data) {

              $event = Event::create($data);

              $seatsData = [];
              $rows = range('A', chr(64 + $event->total_rows));

              foreach ($rows as $row) {
                  for ($col = 1; $col <= $event->total_columns; $col++) {
                      $seatsData[] = [
                          'event_id' => $event->id,
                          'seat_row' => $row,
                          'seat_column' => $col,
                          'status' => 'available',
                          'created_at' => now(),
                          'updated_at' => now(),
                      ];
                  }
              }

              Seat::insert($seatsData);

              Cache::forget('events.all');

              return $event;
          });
      }


       public function getEventWithSeats($eventId)
  {
      return Cache::remember("event.{$eventId}.seats", 30, function () use ($eventId) {
          return Event::with(['seats' => function ($query) {
              $query->orderBy('seat_row')->orderBy('seat_column');
          }])->findOrFail($eventId);
      });
  }

  
}