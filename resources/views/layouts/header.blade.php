<!-- Page Header Start-->
<div class="header dashboards-header">
    <div class="header-menu-header">
        <div class="header-left-header">
            <div class="header-wordpress">
                <a href="{{ url('/') }}">
                    <img class="header-logo" src="{{ asset('assets/images/logo/logo.png') }}" alt="Logo">
                </a>
            </div>
        </div>
        <div class="header-right-header">
            <div class="header-profile-header">
                <a href="javaScript:void(0);" data-bs-toggle="dropdown">
                    <div class="profile-dropdown">
                        <img src="{{ asset('assets/images/dashboard/profile.png') }}" alt="image">
                    </div>
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Page Header Ends-->
