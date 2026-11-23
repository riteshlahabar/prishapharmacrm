{{-- Cuba page header: .page-header > .header-wrapper.row --}}
<div class="page-header">
    <div class="header-wrapper row m-0">
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
            {{-- No search and no notification bell: neither is wired to
                 anything here, and an icon that does nothing is worse than
                 no icon. --}}
            <ul class="nav-menus">
                <li>
                    <div class="mode">
                        <i class="fa-regular fa-moon"></i>
                    </div>
                </li>
                {{--
                    Cuba 5 styles this block through `.profile-media .flex-grow-1`
                    and sizes the avatar with `.img-30`. Bootstrap 4's `.media` /
                    `.media-body` do not exist in Bootstrap 5 and carry no rules
                    here, which left the header 124px tall against the 80px
                    `.page-body` margin-top — hiding the top of every page.
                --}}
                <li class="profile-nav onhover-dropdown pe-0 py-0">
                    <div class="d-flex profile-media align-items-center">
                        <img class="b-r-10 img-30"
                             src="{{ asset('assets/images/dashboard/profile.png') }}"
                             alt="{{ auth()->user()->name ?? 'User' }}">
                        <div class="flex-grow-1">
                            <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                            <p class="mb-0 font-roboto">
                                {{ auth()->user()?->roleLabel() ?? '' }}
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
