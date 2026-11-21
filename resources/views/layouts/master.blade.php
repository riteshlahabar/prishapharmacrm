<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.head')
    @include('layouts.css')
    @yield('css')
</head>
<body>
    <!-- loader starts-->
    <div class="loader-wrapper">
        <div class="loader-index">
            <div class="loader"></div>
        </div>
    </div>
    <!-- loader ends-->

    @include('layouts.header')
    @include('layouts.sidebar')

    @yield('content')

    @include('layouts.footer')
    @include('layouts.scripts')

    @stack('scripts')
</body>
</html>
