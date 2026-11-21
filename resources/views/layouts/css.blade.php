{{--
    Stylesheet order matters: Bootstrap first, then Cuba's style.css, which is
    written as a layer of overrides on top of it (its .btn-primary, .card and
    .table rules only set colours and spacing and assume Bootstrap supplies the
    base, and .row / .col-* exist nowhere else). Without vendors/bootstrap.css
    the grid collapses to stacked blocks and buttons render as text the colour
    of their own background.
--}}
<!-- Font Awesome-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/fontawesome.css') }}">
<!-- Favicons -->
<link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/png">
<!-- Google font-->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&amp;family=Open+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
<!-- Bootstrap 5.3 (base for every Cuba component rule) -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/bootstrap.css') }}">
<!-- Feather icons -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/feather-icon.css') }}">
<!-- Cuba theme -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}">
