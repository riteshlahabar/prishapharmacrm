{{--
    Cuba sidebar. Class contract taken from scss/layout/_sidebar.scss and
    js/sidebar-menu.js: .sidebar-wrapper > .logo-wrapper / .logo-icon-wrapper /
    nav.sidebar-main > #sidebar-menu > ul.sidebar-links#simple-bar >
    li.sidebar-list > a.sidebar-link.sidebar-title + ul.sidebar-submenu.
    The JS binds to #sidebar-menu and simplebar binds to #simple-bar.
--}}
<div class="sidebar-wrapper">
    <div class="logo-wrapper">
        <a href="{{ url('/') }}">
            <img class="img-fluid for-light" src="{{ asset('assets/images/logo/logo.png') }}" alt="Prisha Pharma">
            <img class="img-fluid for-dark" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="Prisha Pharma">
        </a>
        <div class="back-btn"><i class="fa-solid fa-angle-left"></i></div>
        <div class="toggle-sidebar">
            <i class="status_toggle middle sidebar-toggle" data-feather="grid"></i>
        </div>
    </div>
    <div class="logo-icon-wrapper">
        <a href="{{ url('/') }}">
            <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="Prisha Pharma">
        </a>
    </div>

    <nav class="sidebar-main">
        <div class="left-arrow" id="left-arrow"><i class="fa-solid fa-angle-left"></i></div>

        <div id="sidebar-menu">
            <ul class="sidebar-links" id="simple-bar">
                <li class="back-btn">
                    <a href="{{ url('/') }}">
                        <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="">
                    </a>
                    <div class="mobile-back text-end">
                        <span>Back</span><i class="fa-solid fa-angle-right ps-2"></i>
                    </div>
                </li>

                <li class="pin-title sidebar-main-title">
                    <div><h6>Pinned</h6></div>
                </li>

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

                <li class="sidebar-main-title">
                    <div><h6>Masters</h6></div>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="users"></i><span>Customers</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('customers.create') }}">Add Customer</a></li>
                    </ul>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="package"></i><span>Products</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('products.index') }}">All Products</a></li>
                        <li><a href="{{ route('products.create') }}">Add Product</a></li>
                    </ul>
                </li>

                <li class="sidebar-main-title">
                    <div><h6>Operations</h6></div>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="shopping-cart"></i><span>Orders</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('orders.index') }}">All Orders</a></li>
                        <li><a href="{{ route('orders.create') }}">New Order</a></li>
                    </ul>
                </li>

                <li class="sidebar-list">
                    <i class="fa-solid fa-thumbtack"></i>
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="bar-chart-2"></i><span>Reports</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('reports.inventory') }}">Inventory Report</a></li>
                    </ul>
                </li>
            </ul>
        </div>

        <div class="right-arrow" id="right-arrow"><i class="fa-solid fa-angle-right"></i></div>
    </nav>
</div>
