@extends('layouts.app')

  @section('title', 'Create Event')

  @section('content')

      <h1>Create New Event</h1>

      <form action="{{ route('events.store') }}" method="POST" class="event-form">
          @csrf

          <div class="form-group">
              <label for="name">Event Name</label>
              <input type="text" name="event_name" id="event_name" value="{{ old('event_name') }}">
              @error('event_name') <span class="error">{{ $message }}</span> @enderror
          </div>

          <div class="form-group">
              <label for="venue">Venue</label>
              <input type="text" name="event_venue" id="event_venue" value="{{ old('event_venue') }}" >
              @error('event_venue') <span class="error">{{ $message }}</span> @enderror
          </div>

          <div class="form-group">
              <label for="event_date">Event Date & Time</label>
              <input type="datetime-local" name="event_date" id="event_date" value="{{ old('event_date') }}">
              @error('event_date') <span class="error">{{ $message }}</span> @enderror
          </div>

          <div class="form-group">
              <label for="total_rows">Total Rows (max 26 = A to Z)</label>
              <input type="number" name="total_rows" id="total_rows" min="1" max="26" value="{{ old('total_rows',
  5) }}">
              @error('total_rows') <span class="error">{{ $message }}</span> @enderror
          </div>

          <div class="form-group">
              <label for="total_columns">Total Columns</label>
              <input type="number" name="total_columns" id="total_columns" min="1" max="50" value="{{
  old('total_columns', 10) }}">
              @error('total_columns') <span class="error">{{ $message }}</span> @enderror
          </div>

          <button type="submit" class="btn btn-success">Create Event</button>
          <a href="{{ route('home') }}" class="btn btn-danger">Cancel</a>
      </form>

  @endsection

  @push('scripts')
      <script src="{{ asset('js/event-form-validation.js') }}"></script>
  @endpush