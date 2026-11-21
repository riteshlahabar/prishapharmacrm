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

    <!-- tap on top starts-->
    <div class="tap-top"><i class="fa-solid fa-angle-up"></i></div>
    <!-- tap on top ends-->

    {{--
        Cuba requires this exact nesting: .page-wrapper.<layout> > .page-header
        + .page-body-wrapper > .sidebar-wrapper + .page-body + footer.
        style.css nests ~1800 rules under .page-wrapper and ~1100 under
        .page-body-wrapper, and sidebar-menu.js queries #pageWrapper, so
        dropping any of these wrappers silently disables the whole theme.
        Views supply their own <div class="page-body"> inside @section('content').
    --}}
    <div class="page-wrapper compact-wrapper" id="pageWrapper">

        @include('layouts.header')

        <div class="page-body-wrapper">

            @include('layouts.sidebar')

            @yield('content')

            @include('layouts.footer')

        </div>
    </div>

    @include('layouts.scripts')
    @stack('scripts')
</body>
</html>
