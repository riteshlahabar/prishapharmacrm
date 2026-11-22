{{--
    Cuba sidebar. Class contract taken from scss/layout/_sidebar.scss and
    js/sidebar-menu.js: .sidebar-wrapper > .logo-wrapper / .logo-icon-wrapper /
    nav.sidebar-main > #sidebar-menu > ul.sidebar-links#simple-bar >
    li.sidebar-list > a.sidebar-link.sidebar-title + ul.sidebar-submenu.
    The JS binds to #sidebar-menu and simplebar binds to #simple-bar.

    Every entry is wrapped in @can with a permission from
    App\Modules\Access\Support\PermissionCatalog, so a telecaller sees exactly
    what the admin granted. A section title is only emitted when at least one
    of its entries survives, otherwise the sidebar shows empty headings.
--}}
@php
    $canMasters = auth()->user()?->canAny(['regions.view', 'persons.view']) ?? false;
    $canUsers = auth()->user()?->canAny(['mrs.view', 'telecallers.view']) ?? false;
    $canField = auth()->user()?->canAny(['attendance.view', 'visits.view']) ?? false;
    $canAccess = auth()->user()?->can('access.manage') ?? false;
@endphp

<div class="sidebar-wrapper">
    <div class="logo-wrapper">
        <a href="{{ route('dashboard') }}">
            <img class="img-fluid for-light" src="{{ asset('assets/images/logo/logo.png') }}" alt="Prisha Pharma">
            <img class="img-fluid for-dark" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="Prisha Pharma">
        </a>
        <div class="back-btn"><i class="fa-solid fa-angle-left"></i></div>
        <div class="toggle-sidebar">
            <i class="status_toggle middle sidebar-toggle" data-feather="grid"></i>
        </div>
    </div>
    <div class="logo-icon-wrapper">
        <a href="{{ route('dashboard') }}">
            <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="Prisha Pharma">
        </a>
    </div>

    <nav class="sidebar-main">
        <div class="left-arrow" id="left-arrow"><i class="fa-solid fa-angle-left"></i></div>

        <div id="sidebar-menu">
            <ul class="sidebar-links" id="simple-bar">
                <li class="back-btn">
                    <a href="{{ route('dashboard') }}">
                        <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="">
                    </a>
                    <div class="mobile-back text-end">
                        <span>Back</span><i class="fa-solid fa-angle-right ps-2"></i>
                    </div>
                </li>

                <li class="pin-title sidebar-main-title">
                    <div><h6>Pinned</h6></div>
                </li>

                @can('dashboard.view')
                <li class="sidebar-main-title">
                    <div><h6>General</h6></div>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       href="{{ route('dashboard') }}">
                        <i data-feather="home"></i><span>Dashboard</span>
                    </a>
                </li>
                @endcan

                @if($canMasters)
                <li class="sidebar-main-title">
                    <div><h6>Masters</h6></div>
                </li>
                @endif

                @can('regions.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('regions.*') ? 'active' : '' }}" href="#">
                        <i data-feather="map-pin"></i><span>Regions</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('regions.index') }}">All Regions</a></li>
                        @can('regions.create')
                        <li><a href="{{ route('regions.create') }}">Add Region</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('persons.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('persons.*') ? 'active' : '' }}" href="#">
                        <i data-feather="users"></i><span>Persons</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('persons.index') }}">All Persons</a></li>
                        @can('persons.create')
                        <li><a href="{{ route('persons.create') }}">Add Person</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @if($canField)
                <li class="sidebar-main-title">
                    <div><h6>Field</h6></div>
                </li>
                @endif

                @can('attendance.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('attendance.*') ? 'active' : '' }}"
                       href="{{ route('attendance.index') }}">
                        <i data-feather="clock"></i><span>Attendance</span>
                    </a>
                </li>
                @endcan

                @can('visits.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('visits.*') ? 'active' : '' }}"
                       href="{{ route('visits.index') }}">
                        <i data-feather="briefcase"></i><span>Visits</span>
                    </a>
                </li>
                @endcan

                @if($canUsers || $canAccess)
                <li class="sidebar-main-title">
                    <div><h6>People</h6></div>
                </li>
                @endif

                @can('mrs.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('mrs.*') ? 'active' : '' }}" href="#">
                        <i data-feather="user-check"></i><span>Medical Reps</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('mrs.index') }}">All Medical Reps</a></li>
                        @can('mrs.create')
                        <li><a href="{{ route('mrs.create') }}">Add Medical Rep</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('telecallers.view')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('telecallers.*') ? 'active' : '' }}" href="#">
                        <i data-feather="headphones"></i><span>Telecallers</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('telecallers.index') }}">All Telecallers</a></li>
                        @can('telecallers.create')
                        <li><a href="{{ route('telecallers.create') }}">Add Telecaller</a></li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('access.manage')
                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title link-nav {{ request()->routeIs('access.*') ? 'active' : '' }}"
                       href="{{ route('access.permissions.index') }}">
                        <i data-feather="shield"></i><span>Permissions</span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>

        <div class="right-arrow" id="right-arrow"><i class="fa-solid fa-angle-right"></i></div>
    </nav>
</div>
