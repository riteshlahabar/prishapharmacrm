<!-- Page Sidebar Start-->
<div class="sidebar-wrapper" data-sidebar-layout="navigations">
    <div class="logo-wrapper">
        <a href="{{ url('/') }}">
            <img class="img-fluid for-light" src="{{ asset('assets/images/logo/logo-white.png') }}" alt="">
            <img class="img-fluid for-dark" src="{{ asset('assets/images/logo/logo-white.png') }}" alt="">
            <img class="darklogo img-fluid for-light" src="{{ asset('assets/images/logo/logo-white.png') }}" alt="">
            <img class="darklogo-dark img-fluid for-dark" src="{{ asset('assets/images/logo/logo-white.png') }}" alt="">
        </a>
        <div class="back-btn">
            <i class="fa-solid fa-angle-left"></i>
        </div>
        <div class="toggle-btn">
            <i class="fe fe-arrow-left"></i>
        </div>
    </div>
    <div class="logo-icon-wrapper">
        <a href="{{ url('/') }}">
            <img class="img-fluid" src="{{ asset('assets/images/logo/logo-icon.png') }}" alt="">
        </a>
    </div>
    <nav class="sidebar-main">
        <div class="left-arrow" id="left-arrow">
            <i class="fa-solid fa-angle-left"></i>
        </div>
        <div class="left-arrow" id="right-arrow">
            <i class="fa-solid fa-angle-right"></i>
        </div>
        <div class="mobile-menu-navigation" id="mobile-menu-navigation">
            <ul class="sidebar-menu navigation" id="sidebar-menu">
                <li class="sidebar-back"><a href="#"><i class="fa-solid fa-angle-left"></i></a></li>
                <li class="sidebar-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-link">
                        <span class="avatar">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M3.5 13h4.5v6h-4.5zM16 21h4.5v-6h-4.5zM9.5 13h4.5v6h-4.5zM11 1.5L5 8v11a1.5 1.5 0 001.5 1.5h11a1.5 1.5 0 001.5-1.5V8l-6-6.5zM20 8.5v11a1.5 1.5 0 01-1.5 1.5H16v-8.5a1 1 0 011-1h3V6.92a2.5 2.5 0 00-.67-1.74L16.13 2.5a2.5 2.5 0 00-1.83-.84zM2 6.92v2.58h3a1 1 0 011 1V19H3.5a1.5 1.5 0 01-1.5-1.5v-11L7.7 1.66a2.5 2.5 0 011.8-.84 2.5 2.5 0 011.86.84L13 5V6.2a1 1 0 01-1 1H9V19H6V8.5a1 1 0 00-1-1H2z"></path>
                            </svg>
                        </span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link sidebar-title" href="#" data-bs-toggle="collapse" data-bs-target="#customers">
                        <span class="avatar">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M12 11.976c1.16 0 2.1-1.166 2.1-2.6s-1.046-2.603-2.1-2.603-2.1 1.166-2.1 2.602c0 1.436 1.045 2.601 2.1 2.601M12.9 14.416c-.471-.072-1.342-.114-2.8-.114-3.512 0-6.1 1.638-6.1 3.668v2.444c0 .748.603 1.356 1.348 1.356h12.304c.745 0 1.348-.608 1.348-1.356v-2.444c0-2.03-2.592-3.668-6.104-3.668z"></path>
                            </svg>
                        </span>
                        <span class="menu-text">Customers</span>
                    </a>
                    <ul class="sidebar-menu collapse" id="customers" data-bs-parent="#sidebar-menu">
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('customers.index') }}">All Customers</a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('customers.create') }}">Add Customer</a></li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link sidebar-title" href="#" data-bs-toggle="collapse" data-bs-target="#products">
                        <span class="avatar">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M11.55 12.71l-2.75 2.75-1.414-1.414 2.75-2.75-2.75-2.75 1.414-1.414 2.75 2.75 2.75-2.75 1.414 1.414-2.75 2.75 2.75 2.75-1.414 1.414-2.75-2.75zM18.95 4.634L6.05 4.634C5.112 4.634 4.348 5.4 4.348 6.338v11.324c0 .938.764 1.704 1.702 1.704h12.9c.938 0 1.702-.766 1.702-1.704V6.338c0-.938-.764-1.704-1.702-1.704"></path>
                            </svg>
                        </span>
                        <span class="menu-text">Products</span>
                    </a>
                    <ul class="sidebar-menu collapse" id="products" data-bs-parent="#sidebar-menu">
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('products.index') }}">All Products</a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('products.create') }}">Add Product</a></li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link sidebar-title" href="#" data-bs-toggle="collapse" data-bs-target="#orders">
                        <span class="avatar">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M19.877 5h-15.75v14h15.75v-14zm.323.166V21H3.5V4.666h16.7v.5zm-16.7 15.668h16.7v-.168h-16.7v.168zm0-15.668v15.334V4.166h16.7v-.5h-16.7z"></path>
                            </svg>
                        </span>
                        <span class="menu-text">Orders</span>
                    </a>
                    <ul class="sidebar-menu collapse" id="orders" data-bs-parent="#sidebar-menu">
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('orders.index') }}">All Orders</a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('orders.create') }}">Add Order</a></li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link sidebar-title" href="#" data-bs-toggle="collapse" data-bs-target="#reports">
                        <span class="avatar">
                            <svg class="svg-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M13 3L13 21M13 3L8 8M13 3L18 8"></path>
                            </svg>
                        </span>
                        <span class="menu-text">Reports</span>
                    </a>
                    <ul class="sidebar-menu collapse" id="reports" data-bs-parent="#sidebar-menu">
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('reports.sales') }}">Sales Report</a></li>
                        <li class="sidebar-item"><a class="sidebar-link" href="{{ route('reports.inventory') }}">Inventory Report</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</div>
<!-- Page Sidebar Ends-->
