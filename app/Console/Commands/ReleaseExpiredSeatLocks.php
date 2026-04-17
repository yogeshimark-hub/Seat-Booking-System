<?php

namespace App\Console\Commands;

use App\Services\SeatBookingService;
use Illuminate\Console\Command;

class ReleaseExpiredSeatLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seats:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release seats whose locks have expired';

    /**
     * Execute the console command.
     */
   public function handle(SeatBookingService $seatBookingService): int
      {
          $released = $seatBookingService->releaseExpiredLocks();

          if ($released === 0) {
              $this->info('No expired locks to release.');
          } else {
              $this->info("Released {$released} expired seat lock(s).");
          }

          return Command::SUCCESS;
      }
}
