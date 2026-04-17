<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;

use App\Http\Requests\StoreEventRequests;
use App\Services\EventService;





class EventController extends Controller
{

  public function __construct(protected EventService $eventService){}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      $events = Event::orderBy('event_date', 'asc')->get();
      return view('events.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('events.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequests $request)
    {
        $this->eventService->createEventWithSeats($request->validated());

      return redirect()
          ->route('home')
          ->with('success', 'Event created successfully with all seats!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
  {
      $event = $this->eventService->getEventWithSeats($id);
      return view('events.show', compact('event'));
  }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
