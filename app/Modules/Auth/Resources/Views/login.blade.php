{{--
    Panel sign-in. Standalone page (no .page-wrapper) — Cuba styles it through
    .login-card > .login-main > form.theme-form, all present in style.css.

    The show/hide control uses Cuba's .show-hide markup but its own script:
    assets/js/login.js targets input[name="login[password]"], which is not the
    field name Laravel validation expects.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.head')
    @include('layouts.css')
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row m-0">
            <div class="col-12 p-0">
                <div class="login-card">
                    <div>
                        <div class="logo text-center">
                            <a href="{{ url('/') }}">
                                <img class="img-fluid for-light" src="{{ asset('assets/images/logo/logo.png') }}" alt="Prisha Pharma">
                                <img class="img-fluid for-dark" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="Prisha Pharma">
                            </a>
                        </div>

                        <div class="login-main">
                            <form class="theme-form" method="POST" action="{{ route('login.post') }}">
                                @csrf

                                <h4>Sign in to Prisha Pharma</h4>
                                <p>Admins and telecallers only — medical reps use the MR app.</p>

                                @if($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first() }}
                                </div>
                                @endif

                                @if(session('success'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('success') }}
                                </div>
                                @endif

                                <div class="form-group">
                                    <label class="col-form-label" for="email">Email Address</label>
                                    <input class="form-control" id="email" type="email" name="email"
                                           value="{{ old('email') }}" placeholder="you@prishapharma.com"
                                           required autofocus>
                                </div>

                                <div class="form-group">
                                    <label class="col-form-label" for="password">Password</label>
                                    <div class="form-input position-relative">
                                        <input class="form-control" id="password" type="password" name="password"
                                               placeholder="Your password" required>
                                        <div class="show-hide"><span class="show"></span></div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <div class="checkbox p-0">
                                        <input id="remember" type="checkbox" name="remember" value="1"
                                               {{ old('remember') ? 'checked' : '' }}>
                                        <label class="text-muted" for="remember">Remember this device</label>
                                    </div>
                                    <button class="btn btn-primary btn-block w-100 mt-3" type="submit">Sign in</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Cuba's .show-hide styling, bound to this form's actual field name.
        (function ($) {
            'use strict';
            $('.show-hide span').on('click', function () {
                var $span = $(this);
                var showing = $span.hasClass('show');
                $('#password').attr('type', showing ? 'text' : 'password');
                $span.toggleClass('show', !showing);
            });
        })(jQuery);
    </script>
</body>
</html>
