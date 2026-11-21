{{-- Cuba page header: .page-header > .header-wrapper.row --}}
<div class="page-header">
    <div class="header-wrapper row m-0">
        <form class="form-inline search-full col" action="#" method="get">
            <div class="form-group w-100">
                <div class="Typeahead Typeahead--twitterUsers">
                    <div class="u-posRelative">
                        <input class="demo-input Typeahead-input form-control-plaintext w-100"
                               type="text" placeholder="Search Prisha Pharma .." name="q" autofocus>
                        <div class="spinner-border Typeahead-spinner" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <i class="close-search fa-solid fa-xmark"></i>
                    </div>
                    <div class="Typeahead-menu"></div>
                </div>
            </div>
        </form>

        <div class="header-logo-wrapper col-auto p-0">
            <div class="logo-wrapper">
                <a href="{{ url('/') }}">
                    <img class="img-fluid" src="{{ asset('assets/images/logo/logo.png') }}" alt="Prisha Pharma">
                </a>
            </div>
            <div class="toggle-sidebar">
                <i class="status_toggle middle sidebar-toggle" data-feather="align-center"></i>
            </div>
        </div>

        <div class="left-header col horizontal-wrapper ps-0"></div>

        <div class="nav-right col-8 pull-right right-header p-0">
            <ul class="nav-menus">
                <li>
                    <span class="header-search">
                        <i data-feather="search"></i>
                    </span>
                </li>
                <li class="onhover-dropdown">
                    <div class="notification-box">
                        <i data-feather="bell"></i>
                    </div>
                </li>
                <li>
                    <div class="mode">
                        <i class="fa-regular fa-moon"></i>
                    </div>
                </li>
                <li class="profile-nav onhover-dropdown pe-0 py-0">
                    <div class="media profile-media">
                        <img class="b-r-10"
                             src="{{ asset('assets/images/dashboard/profile.png') }}"
                             alt="{{ auth()->user()->name ?? 'User' }}">
                        <div class="media-body">
                            <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                            <p class="mb-0 font-roboto">
                                {{ auth()->user()->email ?? '' }}
                                <i class="middle fa-solid fa-angle-down"></i>
                            </p>
                        </div>
                    </div>
                    <ul class="profile-dropdown onhover-show-div">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 border-0 text-start">
                                    <i data-feather="log-out"></i><span>Log out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
