@extends('layouts.app')
                                                                                                                    @section('title', 'Register')

  @section('content')

      <div class="auth-box">
          <h1>Create Account</h1>
          <p class="auth-subtitle">Register to book your seats</p>

          <form action="{{ route('register') }}" method="POST" class="register-form" novalidate>
              @csrf

              <div class="form-group">
                  <label for="name">Full Name</label>
                  <input type="text" name="name" id="name" value="{{ old('name') }}">
                  @error('name') <span class="error">{{ $message }}</span> @enderror
              </div>

              <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" name="email" id="email" value="{{ old('email') }}">
                  @error('email') <span class="error">{{ $message }}</span> @enderror
              </div>

              <div class="form-group">
                  <label for="password">Password</label>
                  <input type="password" name="password" id="password">
                  @error('password') <span class="error">{{ $message }}</span> @enderror
              </div>

              <div class="form-group">
                  <label for="password_confirmation">Confirm Password</label>
                  <input type="password" name="password_confirmation" id="password_confirmation">
              </div>

              <button type="submit" class="btn btn-success" style="width: 100%;">Register</button>
          </form>

          <p class="auth-footer">
              Already have an account? <a href="{{ route('login') }}">Login here</a>
          </p>
      </div>

  @endsection

  @push('scripts')
      <script src="{{ asset('js/register-form-validation.js') }}"></script>
  @endpush