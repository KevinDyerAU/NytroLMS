<div class="row clearfix">
    <div class="col-12">
        @include('panels.toast-message')
    </div>
</div>

<div class="content-header row">
    <div class="content-header-left col-md-11 col-10 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                @yield('instead_of_breadcrumbs')
                @if (@isset($breadcrumbs))
                <h2 class="content-header-title border-0 float-start mb-0">@yield('title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        {{-- this will load breadcrumbs dynamically from controller --}}
                        @foreach ($breadcrumbs as $breadcrumb)
                            <li class="breadcrumb-item">
                                @if (isset($breadcrumb['link']))
                                    <a
                                        href="{{ $breadcrumb['link'] == 'javascript:void(0)' ? $breadcrumb['link'] : url($breadcrumb['link']) }}">
                                @endif
                                    {{ $breadcrumb['name'] }}
                                    @if (isset($breadcrumb['link']))
                                        </a>
                                    @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
                @endisset
            </div>
        </div>
    </div>

    @if (isset($actionItems) && !empty($actionItems))
        <div class="content-header-right text-md-end col-md-1 col-2 d-md-block d-none">
            <div class="mb-1 breadcrumb-right">
                <div class="dropdown">
                    <button class="btn-icon btn btn-primary btn-round btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i data-lucide="plus"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach ($actionItems as $actionItem)
                            <a class="dropdown-item {{ $actionItem['class'] ?? '' }}"
                               href="{{ $actionItem['link'] }}"
                               @foreach($actionItem as $key => $value)
                                   @if(substr($key, 0, 5) === 'data-')
                                       {{ $key }}="{{ $value }}"
                                   @endif
                               @endforeach>
                                <i class="me-1" data-lucide="{{ $actionItem['icon'] ?: 'check-square' }}"></i>
                                <span class="align-middle">{{ $actionItem['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
