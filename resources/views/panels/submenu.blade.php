{{-- For submenu --}}
<ul class="menu-content">
    @if (isset($menu))
        @foreach ($menu as $submenu)
            @can(!empty($submenu->permission) ? $submenu->permission : $menu_permission)
                @php $currentRouteName = Route::currentRouteName(); @endphp
                <li @if (
                    \Str::contains(
                        $currentRouteName,
                        \Str::substr($submenu->slug, 0, \Str::length(\Str::beforeLast($currentRouteName, '.'))))) class="active" @endif>
                    <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
                        class="d-flex align-items-center"
                        target="{{ isset($submenu->newTab) && $submenu->newTab === true ? '_blank' : '_self' }}"
                        {{ isset($submenu->forceDownload) && $submenu->forceDownload === true ? 'download' : '' }}>
                        @if (isset($submenu->icon))
                            <i font-weight="bold" data-lucide="{{ $submenu->icon }}"></i>
                        @endif
                        <span class="menu-item text-truncate">{{ __($submenu->name) }}</span>
                    </a>
                    @if (isset($submenu->submenu))
                        @include('panels/submenu', ['menu' => $submenu->submenu])
                    @endif
                </li>
            @endcan
        @endforeach
    @endif
</ul>
