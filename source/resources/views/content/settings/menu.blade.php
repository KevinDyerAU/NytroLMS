<div class='card row'>
    <div class='card-body col-12'>
        <form method='POST' action='{{ route('settings.update', [strtolower($type)]) }}' class="form ">
            @csrf
            <div class='row setting-menu-repeater' id="sidebar-menu-repeater">
                <div class="col-12">
                    <h2>Sidebar Menu</h2>
                </div>
                <div class="col-12" data-repeater-list="menu[sidebar]">
                    @php
                        $sidebarItems = old('settings.menu.sidebar') ?? ($settings['sidebar'] ?? []);
                    @endphp
                    @if (count($sidebarItems) > 0)
                        @foreach ($sidebarItems as $index => $item)
                            <div class="mb-1 d-flex" data-repeater-item>
                                <div class="col-1 me-1 mt-2 pt-75">
                                    <button type="button" class="btn-close text-danger btn-outline-danger"
                                        data-repeater-delete aria-label="Close">
                                    </button>
                                    <span class="fw-bold ms-25"> Menu Item:</span>
                                </div>
                                <div class="col-3 me-1">
                                    <label class="form-label control-label">Title</label>
                                    <input name="settings[menu][sidebar][{{ $index }}][title]" type="text"
                                        class="form-control" value="{{ $item['title'] ?? '' }}" />
                                </div>
                                <div class="col-6 me-1">
                                    <label class="form-label control-label">Link</label>
                                    <input name="settings[menu][sidebar][{{ $index }}][link]" type="text"
                                        class="form-control" value="{{ $item['link'] ?? '' }}" />
                                </div>
                                <div class="col-2 me-1 mt-2 pt-75">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                            name='settings[menu][sidebar][{{ $index }}][target]' value='_blank'
                                            {{ !empty($item['target']) ? 'checked="checked"' : '' }} />
                                        <label class="form-check-label control-label">Open in New Tab</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="mb-1 d-flex" data-repeater-item>
                            <div class="col-1 me-1 mt-2 pt-75">
                                <p class="fw-bolder">Menu Item:</p>
                            </div>
                            <div class="col-3 me-1">
                                <label class="form-label control-label">Title</label>
                                <input name="settings[menu][sidebar][][title]" type="text" class="form-control"
                                    value="" />
                            </div>
                            <div class="col-6 me-1">
                                <label class="form-label control-label">Link</label>
                                <input name="settings[menu][sidebar][][link]" type="text" class="form-control"
                                    value="" />
                            </div>
                            <div class="col-2 me-1 mt-2 pt-75">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        name='settings[menu][sidebar][][target]' value='_blank' checked="checked" />
                                    <label class="form-check-label control-label">Open in New Tab</label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="row mt-1 mb-2">
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary btn-outline-secondary btn-sm btn-add-new"
                            data-repeater-create>
                            <i data-lucide="plus" class="me-25"></i>
                            <span class="align-middle">Add More Links</span>
                        </button>
                    </div>
                </div>
                <div class="row mt-1 mb-2">
                    <div class="col-12 d-flex justify-content-start">
                        <button type="submit"class="btn btn-primary me-1 waves-effect waves-float waves-light">
                            <span class="align-middle">Save Sidebar Menu</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class='card row'>
    <div class='card-body col-12'>
        <form method='POST' action='{{ route('settings.update', [strtolower($type)]) }}' class="form ">
            @csrf
            <div class='row setting-menu-repeater' id="footer-menu-repeater">
                <div class="col-12">
                    <h2>Footer Menu</h2>
                </div>
                <div class="col-12" data-repeater-list="menu[footer]">
                    @php
                        $footerItems = old('settings.menu.footer') ?? ($settings['footer'] ?? []);
                    @endphp
                    @if (count($footerItems) > 0)
                        @foreach ($footerItems as $index => $item)
                            <div class="mb-1 d-flex repeater-wrapper" data-repeater-item>
                                <div class="col-1 me-1 mt-2 pt-75">
                                    <button type="button" class="btn-close text-danger btn-outline-danger"
                                        data-repeater-delete aria-label="Close">
                                    </button>
                                    <span class="fw-bold ms-25"> Menu Item:</span>
                                </div>
                                <div class="col-3 me-1">
                                    <label class="form-label control-label">Title</label>
                                    <input name="settings[menu][footer][{{ $index }}][title]" type="text"
                                        class="form-control" value="{{ $item['title'] ?? '' }}" />
                                </div>
                                <div class="col-6 me-1">
                                    <label class="form-label control-label">Link</label>
                                    <input name="settings[menu][footer][{{ $index }}][link]" type="text"
                                        class="form-control" value="{{ $item['link'] ?? '' }}" />
                                </div>
                                <div class="col-2 me-1 mt-2 pt-75">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                            name='settings[menu][footer][{{ $index }}][target]' value='_blank'
                                            {{ !empty($item['target']) ? 'checked="checked"' : '' }} />
                                        <label class="form-check-label control-label">Open in New Tab</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="mb-1 d-flex repeater-wrapper" data-repeater-item>
                            <div class="col-1 me-1 mt-2 pt-75">
                                <p class="fw-bolder">Menu Item:</p>
                            </div>
                            <div class="col-3 me-1">
                                <label class="form-label control-label">Title</label>
                                <input name="settings[menu][footer][][title]" type="text" class="form-control"
                                    value="{{ old('settings.menu.footer.0.title') ?? '' }}" />
                            </div>
                            <div class="col-6 me-1">
                                <label class="form-label control-label">Link</label>
                                <input name="settings[menu][footer][][link]" type="text" class="form-control"
                                    value="{{ old('settings.menu.footer.0.link') ?? '' }}" />
                            </div>
                            <div class="col-2 me-1 mt-2 pt-75">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        name='settings[menu][footer][][target]' value='_blank' checked="checked" />
                                    <label class="form-check-label control-label">Open in New Tab</label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="row mt-1 mb-2">
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary btn-outline-secondary btn-sm btn-add-new"
                            data-repeater-create>
                            <i data-lucide="plus" class="me-25"></i>
                            <span class="align-middle">Add More Links</span>
                        </button>
                    </div>
                </div>
                <div class="row mt-1 mb-2">
                    <div class="col-12 d-flex justify-content-start">
                        <button type="submit" class="btn btn-primary me-1 waves-effect waves-float waves-light">
                            <span class="align-middle">Save Footer Menu</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
