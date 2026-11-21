<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prisha Pharma CRM</title>
    @include('layouts.head')
    @include('layouts.css')
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-main">
            <div class="logo">
                <a class="logo-dark" href="{{ url('/') }}">
                    <img src="{{ asset('assets/images/logo/logo.png') }}" alt="logo">
                </a>
            </div>
            <div class="form-login">
                <form class="theme-form login-form" action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <h4 class="text-center mb-4">Sign In</h4>
                    <p class="text-center text-muted mb-4">Enter your email & password to access your account</p>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Enter email" required autofocus>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Password</label>
                        <div class="form-password-position">
                            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                            <span class="toggle-password">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                    </div>
                    <div class="form-group mb-4 text-center">
                        <button type="submit" class="btn btn-primary btn-block w-100">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>
