<?php
namespace App\Http\Controllers\auth;                                                                            
                                                                                                                  
  use App\Http\Controllers\Controller;                                                                            
  use Illuminate\Http\Request;
  use App\Models\User;
  use Illuminate\Support\Facades\Hash;
  use Illuminate\Support\Facades\Auth;
  use App\Http\Requests\LoginRequest;
  use App\Http\Requests\RegisterRequest;

  class AuthController extends Controller
  {
      public function showLogin()
      {
          return view('auth.login');
      }

      public function login(LoginRequest $request)
      {
          $credentials = $request->only('email', 'password');

          if (Auth::attempt($credentials, $request->boolean('remember'))) {
              $request->session()->regenerate();

              return $this->redirectAfterAuth('Welcome back, ' . Auth::user()->name . '!');
          }

          return back()
              ->withInput($request->only('email'))
              ->withErrors(['email' => 'Invalid email or password.']);
      }

      public function showRegister()
      {
          return view('auth.register');
      }

      public function register(RegisterRequest $request)
      {
          $user = User::create([
              'name' => $request->name,
              'email' => $request->email,
              'password' => Hash::make($request->password),
          ]);

          Auth::login($user);

          return $this->redirectAfterAuth('Welcome, ' . $user->name . '! Your account is created.');
      }

      public function logout(Request $request)
      {
          Auth::logout();

          $request->session()->invalidate();
          $request->session()->regenerateToken();

          return redirect()
              ->route('home')
              ->with('success', 'You have been logged out.');
      }

      private function redirectAfterAuth(string $message)
      {
          if (session()->has('pending_booking')) {
              $pending = session()->pull('pending_booking');
              $seatIdsParam = implode(',', $pending['seat_ids']);

              return redirect()
                  ->route('events.show', $pending['event_id'])
                  ->with('success', $message)
                  ->with('restore_seats', $seatIdsParam);
          }

          return redirect()
              ->route('home')
              ->with('success', $message);
      }
  }