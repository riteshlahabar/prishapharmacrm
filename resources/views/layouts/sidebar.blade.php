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
    $canUsers = auth()->user()?->canAny(['mrs.view', 'telecallers.view']) ?? false;
    $canAccess = auth()->user()?->can('access.manage') ?? false;
    $isAdmin = auth()->user()?->isAdmin() ?? false;
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

                {{--
                    Legacy generic-CRM screens. Admin-only and unlabelled in the
                    permission catalog on purpose: Phase 2 replaces customers
                    with persons and drops products and orders entirely.
                --}}
                @if($isAdmin)
                <li class="sidebar-main-title">
                    <div><h6>Legacy (retiring in Phase 2)</h6></div>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="#">
                        <i data-feather="users"></i><span>Customers</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('customers.create') }}">Add Customer</a></li>
                    </ul>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('products.*') ? 'active' : '' }}" href="#">
                        <i data-feather="package"></i><span>Products</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('products.index') }}">All Products</a></li>
                        <li><a href="{{ route('products.create') }}">Add Product</a></li>
                    </ul>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="#">
                        <i data-feather="shopping-cart"></i><span>Orders</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('orders.index') }}">All Orders</a></li>
                        <li><a href="{{ route('orders.create') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#">
                        <i data-feather="bar-chart-2"></i><span>Reports</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.inventory') }}">Inventory Report</a></li>
                    </ul>
                </li>
                @endif
            </ul>
        </div>

        <div class="right-arrow" id="right-arrow"><i class="fa-solid fa-angle-right"></i></div>
    </nav>
</div>
