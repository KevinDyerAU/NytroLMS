@if ($configData['mainLayoutType'] === 'horizontal' && isset($configData['mainLayoutType']))
    <nav class="header-navbar navbar-expand-lg navbar navbar-fixed align-items-center navbar-shadow navbar-brand-center {{ $configData['navbarColor'] }}"
        data-nav="brand-center">
        <div class="navbar-header d-xl-block d-none">
            <ul class="nav navbar-nav">
                <li class="nav-item">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <img src="{{ asset('images/ico/favicon-32x32.png') }}"
                            alt="{{ config('settings.site.institute_name', 'Key Institute') }}" />
                        <h2 class="brand-text text-primary ms-1">
                            {{ config('settings.site.institute_name', 'Key Institute') }}</h2>
                    </a>
                </li>
            </ul>
        </div>
    @else
        <nav
            class="header-navbar navbar navbar-expand-lg align-items-center {{ $configData['navbarClass'] }} navbar-light navbar-shadow {{ $configData['navbarColor'] }} {{ $configData['layoutWidth'] === 'boxed' && $configData['verticalMenuNavbarType'] === 'navbar-floating' ? 'container-xxl' : '' }}">
@endif
<div class="navbar-container d-flex content">
    <div class="bookmark-wrapper d-flex align-items-center">
        <ul class="nav navbar-nav d-xl-none">
            <li class="nav-item"><a class="nav-link menu-toggle" href="javascript:void(0);"><i class="ficon"
                        data-lucide="menu"></i></a></li>
        </ul>
        <ul class="nav navbar-nav">
            <li class="nav-item d-none d-lg-block">
                <a class="nav-link nav-link-style">
                    <i class="ficon" data-lucide="{{ $configData['theme'] === 'dark' ? 'sun' : 'moon' }}"></i>
                </a>
            </li>
        </ul>
    </div>

    {{-- SHOWS ENVIRONMENT IN NAV BAR --}}
    @if (app()->environment('local') || app()->environment('development'))
        <ul class="nav navbar-nav">
            <li class="nav-item d-none d-lg-block bg-danger text-white fw-bold text-uppercase p-1">
                <strong>DEV</strong>
            </li>
        </ul>
    @endif

    <ul class="nav navbar-nav align-items-center ms-auto">
        @if (Route::currentRouteName() === 'frontend.course_manager.*')
            <li class="nav-item dropdown dropdown-language">
                <a class="nav-link dropdown-toggle" id="dropdown-flag" href="#" data-bs-toggle="dropdown"
                    aria-haspopup="true">
                    <i class="flag-icon flag-icon-us"></i>
                    <span class="selected-language">English</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-flag">
                    <a class="dropdown-item" href="{{ url('lang/en') }}" data-language="en">
                        <i class="flag-icon flag-icon-us"></i> English
                    </a>
                    <a class="dropdown-item" href="{{ url('lang/fr') }}" data-language="fr">
                        <i class="flag-icon flag-icon-fr"></i> French
                    </a>
                    <a class="dropdown-item" href="{{ url('lang/de') }}" data-language="de">
                        <i class="flag-icon flag-icon-de"></i> German
                    </a>
                    <a class="dropdown-item" href="{{ url('lang/pt') }}" data-language="pt">
                        <i class="flag-icon flag-icon-pt"></i> Portuguese
                    </a>
                </div>
            </li>
        @endif
        @auth
            {{--        @include('panels.notification') --}}
            <li class="nav-item dropdown dropdown-user">
                <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="javascript:void(0);"
                    data-bs-toggle="dropdown" aria-haspopup="true">
                    <div class="user-nav d-sm-flex d-none">
                        <span class="user-name fw-bolder">{{ auth()->user()->name }}</span>
                        <span class="user-status">{{ auth()->user()->roles->first()->name }} -
                            {{ auth()->user()->id }}
                        </span>
                    </div>
                    <span class="avatar bg-light-primary">
                        @if (!empty(auth()->user()->avatar()))
                            <img class="round" src="{{ auth()->user()->avatar() }}" alt="avatar" height="40"
                                width="40">
                        @else
                            <span
                                class="avatar-content">{{ ucwords(auth()->user()->first_name[0] . auth()->user()->last_name[0]) }}</span>
                        @endif
                        <span class="avatar-status-online"></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
                    @if (auth()->user()->hasRole('Student'))
                        <a class="dropdown-item" href="{{ route('frontend.dashboard') }}">
                            <i class="me-50" data-lucide="house"></i> Dashboard
                        </a>
                    @else
                        <a class="dropdown-item" href="{{ route('home') }}">
                            <i class="me-50" data-lucide="layout"></i> Website
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                            <i class="me-50" data-lucide="house"></i> Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('profile.show', auth()->user()) }}">
                            <i class="me-50" data-lucide="user"></i> Profile
                        </a>
                        @can('update settings')
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('settings.index') }}">
                                <i class="me-50" data-lucide="settings"></i> Settings
                            </a>
                        @endcan
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('profile.password', auth()->user()) }}">
                        <i class="me-50" data-lucide="user"></i> Reset Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                        onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                        <i class="me-50" data-lucide="power"></i> {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </li>
        @else
            <li class="nav-item">
                <a class="btn btn-outline-primary" href="{{ url('login') }}">
                    <i data-lucide="log-in"></i> Login
                </a>
            </li>
        @endauth
    </ul>
</div>
</nav>

<!-- END: Header-->
