@php
    $configData = Helper::applClasses();
    $sidebarMenu = $settings['sidebar'] ?? null;
@endphp
<div class="main-menu menu-fixed {{ $configData['theme'] === 'dark' || $configData['theme'] === 'semi-dark' ? 'menu-dark' : 'menu-light' }} menu-accordion menu-shadow"
    data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item me-auto">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png') }}"
                        alt="{{ config('settings.site.institute_name', 'Key Institute') }}"
                        style="height: 50px; width: 100%;" />
                    {{--                    <h2 class="brand-text text-primary ms-1">{{ config('settings.site.institute_name', 'Key Institute') }}</h2> --}}
                </a>
            </li>
            <li class="nav-item nav-toggle">
                <a class="nav-link modern-nav-toggle pe-0" data-toggle="collapse">
                    <i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-lucide="x"></i>
                    <i class="d-none d-xl-block collapse-toggle-icon font-medium-4 text-primary" data-lucide="disc"
                        data-ticon="disc"></i>
                </a>
            </li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            {{-- Foreach menu item starts --}}
            @if (isset($menuData[0]))
                @foreach ($menuData[0]->menu as $menu)
                    @if (isset($menu->navheader))
                        <li class="navigation-header">
                            <span>{{ __('locale.' . $menu->navheader) }}</span>
                            <i data-lucide="more-horizontal"></i>
                        </li>
                    @else
                        {{-- Add Custom Class with nav-item --}}
                        @php
                            $custom_classes = '';
                            if (isset($menu->classlist)) {
                                $custom_classes = $menu->classlist;
                            }
                        @endphp
                        @can($menu->permission ?? true)
                            <li
                                class="nav-item {{ $custom_classes }} {{ Route::currentRouteName() === $menu->slug ? 'active' : '' }}">
                                <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0)' }}"
                                    class="d-flex align-items-center"
                                    target="{{ isset($menu->newTab) ? '_blank' : '_self' }}">
                                    <i data-lucide="{{ $menu->icon }}"></i>
                                    <span class="menu-title text-truncate">{{ __($menu->name) }}</span>
                                    @if (isset($menu->badge))
                                        <?php $badgeClasses = 'badge rounded-pill badge-light-primary ms-auto me-1'; ?>
                                        <span
                                            class="{{ isset($menu->badgeClass) ? $menu->badgeClass : $badgeClasses }}">{{ $menu->badge }}</span>
                                    @endif
                                </a>
                                @if (isset($menu->submenu))
                                    @include('panels/submenu', [
                                        'menu' => $menu->submenu,
                                        'menu_permission' => $menu->permission,
                                    ])
                                @endif
                            </li>
                        @endcan
                    @endif
                @endforeach
            @endif
            @if (!empty($sidebarMenu))
                @foreach ($sidebarMenu as $menu)
                    <li class="nav-item">
                        <a href="{{ isset($menu['link']) ? url($menu['link']) : 'javascript:void(0)' }}"
                            class="d-flex align-items-center" target="{{ isset($menu['target']) ? '_blank' : '_self' }}">
                            <i
                                data-lucide="{{ isset($menu['target']) && $menu['target'] === '_blank' ? 'external-link' : 'link' }}"></i>
                            <span class="menu-title text-truncate">{{ __($menu['title']) }}</span>
                        </a>
                    </li>
                @endforeach
            @endif

            {{-- Foreach menu item ends --}}
            @if (auth()->user()->email === 'mohsin@inceptionsol.com' || auth()->user()->username === 'mohsina')
                <li class="nav-item m-50">
                    <a href="/telescope" class="d-flex align-items-center text-info bg-light" target="_blank">
                        <i data-lucide="link-2"></i>
                        <span class="menu-title text-truncate">Telescope</span>
                    </a>
                </li>
                <li class="nav-item m-50">
                    <a href="/log-viewer" class="d-flex align-items-center text-info bg-light" target="_blank">
                        <i data-lucide="link-2"></i>
                        <span class="menu-title text-truncate">System Logs</span>
                    </a>
                </li>
                <li class="nav-item m-50">
                    <a href="/playground" class="d-flex align-items-center text-danger bg-light fw-bold"
                        target="_blank">
                        <i data-lucide="link-2"></i>
                        <span class="menu-title text-truncate">Playground</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
<!-- END: Main Menu-->
