@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')

    <div class="auth-box">
        <h1>Admin / Vendor Login</h1>
        <p class="auth-subtitle">Only admins and vendors can create events.</p>

        <form action="{{ route('admin.login') }}" method="POST" class="login-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" autofocus>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group remember-group">
                <label>
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%;">Login</button>
        </form>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/login-form-validation.js') }}"></script>
@endpush
