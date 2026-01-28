@extends('layouts/contentLayoutMaster')

@section('title', 'Companies')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
@endsection


@section('page-style')
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-2">
                <div class="card-body" id="tabs">
                    <ul class="nav nav-pills nav-fill">
                        <li class="nav-item">
                            <a class="nav-link active" id="company-overview" data-bs-toggle="pill"
                                href="#company-overview-tab" aria-expanded="true">Overview</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="company-leaders" data-bs-toggle="pill"
                                onclick="Company.leaders('{{ $company->id }}')" href="#company-leaders-tab"
                                aria-expanded="false">
                                <span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                Leaders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="company-students" data-bs-toggle="pill"
                                onclick="Company.students('{{ $company->id }}')" href="#company-students-tab"
                                aria-expanded="false">
                                <span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="company-notes" data-bs-toggle="pill"
                                onclick="Tabs.showNotes('company','{{ $company->id }}')" href="#company-notes-tab"
                                aria-expanded="false"><span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                Notes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="company-signup" data-bs-toggle="pill" href="#company-signup-tab"
                                aria-expanded="true">Signup Link</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-content'>
        <div class='tab-pane active' id='company-overview-tab' role='tabpanel' aria-labelledby='company-overview'
            aria-expanded='true'>
            <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>{{ $company->name }}</h2>
                        </div>
                        <div class='card-body'>
                            <div class="clearfix divider divider-secondary divider-start-center ">
                                <span class="divider-text text-dark"> Company</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                                <span class='col-sm-6'>{{ $company->email ?? '' }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                                <span class='col-sm-6'>{{ $company->address ?? '' }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder me-25 col-sm-4 text-end'>Contact No:</span>
                                <span class='col-sm-6'>{{ $company->number ?? '' }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder me-25 col-sm-4 text-end'>Added On:</span>
                                <span class='col-sm-6'>{{ $company->created_at ?? '' }}</span>
                            </div>
                            @if (!empty($company->pocUser))
                                <div class='row mb-2'>
                                    <span class='fw-bolder me-25 col-sm-4 text-end'>BRM:</span>
                                    <span class='col-sm-6'>
                                        <a href="{{ route('user_management.users.show', $company->pocUser->id) }}"
                                            title="{{ $company->pocUser->name }}">
                                            {{ $company->pocUser->name }}
                                        </a>
                                    </span>
                                </div>
                                {{--                                <div class='row mb-2'> --}}
                                {{--                                    <span class='fw-bolder me-25 col-sm-4 text-end'>POC User Role:</span> --}}
                                {{--                                    <span class='col-sm-6'>{{ $company->pocUser->roles->first()->name }}</span> --}}
                                {{--                                </div> --}}
                            @endif
                            @if (!empty($company->bmUser))
                                <div class='row mb-2'>
                                    <span class='fw-bolder me-25 col-sm-4 text-end'>BM:</span>
                                    <span class='col-sm-6'>
                                        <a href="{{ route('account_manager.leaders.show', $company->bmUser->id) }}"
                                            title="{{ $company->bmUser->name }}">
                                            {{ $company->bmUser->name }}
                                        </a>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class='tab-pane' id='company-leaders-tab' role='tabpanel' aria-labelledby='company-leaders'
            aria-expanded='false'>
        </div>

        <div class='tab-pane' id='company-students-tab' role='tabpanel' aria-labelledby='company-students'
            aria-expanded='false'>
        </div>

        <div class='tab-pane' id='company-notes-tab' role='tabpanel' aria-labelledby='company-notes'
            aria-expanded='false'>
            @if (auth()->user()->can('create notes'))
                <div id="note_input_wrapper" class="d-print-none">
                    {{ Widget::run('addNote', ['input_id' => 'note_body2', 'subject_type' => 'company', 'subject_id' => $company->id]) }}
                </div>
            @endif
            <div class="content-notes"></div>
        </div>

        <div class='tab-pane' id='company-signup-tab' role='tabpanel' aria-labelledby='company-signup'
            aria-expanded='false'>
            <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Company: {{ $company->name }}</h2>
                        </div>
                        <div class='card-body'>
                            <div class="clearfix divider divider-secondary divider-start-center ">
                                <span class="divider-text text-dark">Create a Signup Link for selected course</span>
                            </div>
                            <form action="{{ route('account_manager.companies.signup', $company) }}" method="post">
                                <div class='row'>
                                    @csrf
                                    <div class='col-6'>

                                        <div class="mb-1">
                                            <label class="form-label required" for="role">Select Course(s)</label>
                                            <select data-placeholder="Select Course..." class="select2 form-select"
                                                data-class="@error('course') is-invalid @enderror" id="course"
                                                name="course">
                                                <option></option>
                                                @php $category = '' @endphp
                                                @foreach ($courses as $course)
                                                    @if ($category !== $course->category)
                                                        @if ($category !== '')
                                                            {{ '</optgroup>' }}
                                                        @endif
                                                        <optgroup
                                                            label="{{ config('lms.course_category')[!empty($course->category) ? $course->category : 'uncategorized'] }}">
                                                    @endif

                                                    <option data-length='{{ $course->course_length_days }}'
                                                        value="{{ $course->id }}"
                                                        {{ old('course') == $course->id ? 'selected="selected"' : '' }}>
                                                        {{ $course->title }}</option>

                                                    @php $category = $course->category  @endphp
                                                @endforeach
                                                </optgroup>
                                            </select>
                                            @error('course')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class='col-6'>
                                        <div class="mb-1">
                                            <label class="form-label required" for="leaders">Attach Leader</label>
                                            <select data-placeholder="Select a Leader..."
                                                class="select2 form-select required"
                                                data-class=" @error('leaders') is-invalid @enderror" id="leaders"
                                                name='leaders'>
                                                <option></option>
                                                @if (!empty($leaders))
                                                    @foreach ($leaders as $leader)
                                                        <option value="{{ $leader->id }}"
                                                            {{ !empty(old('leaders')) && $leader->id == old('leaders')
                                                                ? 'selected=selected'
                                                                : (!empty($student) && in_array($leader->id, $student->leaders->pluck('id')->toArray())
                                                                    ? 'selected=selected'
                                                                    : '') }}>
                                                            {{ \Str::title($leader->name) . " <{$leader->email}>" }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('leaders')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    {{--                                    <div class='col-6'> --}}
                                    {{--                                        <div class="mb-1"> --}}
                                    {{--                                            <div class=' form-check form-switch'> --}}
                                    {{--                                                <input type="checkbox" name="is_chargeable" id="is_chargeable" value="1" --}}
                                    {{--                                                       class="form-check-input" {{ old('is_chargeable') ? 'checked' : '' }}> --}}
                                    {{--                                                <label class="form-check-label required" for="is_chargeable">Is Chargeable</label> --}}
                                    {{--                                                @error('is_chargeable') --}}
                                    {{--                                                <div class="invalid-feedback">{{ $message }}</div> --}}
                                    {{--                                                @enderror --}}
                                    {{--                                            </div> --}}
                                    {{--                                        </div> --}}
                                    {{--                                    </div> --}}
                                    <div class='col-12'>
                                        <button type="submit"
                                            class="btn btn-primary me-1 waves-effect waves-float waves-light">
                                            Generate
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if (count($signupLinks) > 0)
                        <div class='card'>
                            <div class='card-body'>
                                <div class="clearfix divider divider-secondary divider-start-center ">
                                    <span class="divider-text text-dark">Signup Links already created</span>
                                </div>
                                <div class='row'>
                                    <div class="col-12">
                                        <ul class='list-group'>
                                            @foreach ($signupLinks as $link)
                                                <li class='list-group-item'>

                                                    {{--                                                    <strong>Is Chargeable:</strong> <span>{{ $link->is_chargeable? "Yes": "No" }} | </span> --}}
                                                    <strong>Course:</strong> <span>{{ $link->course?->title }} | </span>
                                                    <strong>Leader:</strong> <span>{{ $link->leader?->name }} | </span>
                                                    <strong>Link:</strong> <span><a
                                                            href="{{ route('signup-link', $link->key) }}"
                                                            title="Signup Link"
                                                            target="_blank">{{ route('signup-link', $link->key) }}</a></span>
                                                    <span class="ms-2"> | </span>
                                                    <a href="# "
                                                        onclick="event.preventDefault();document.getElementById('delete-link-{{ $link->id }}').submit();"
                                                        class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                                        <i data-lucide='x-square' class='me-25'></i>
                                                        <span>Delete</span></a>

                                                    <form id="delete-link-{{ $link->id }}"
                                                        action="{{ route('account_manager.companies.deleteLink', $link) }}"
                                                        method="POST" style="display: none;">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/ckeditor/ckeditor.js') }}"></script>
    {{-- TinyMCE for notes (replaces CKEditor for spell checking support) --}}
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.7.0/tinymce.min.js"></script>
    <script src="{{ asset(mix('js/scripts/_my/tinymce-init.js')) }}"></script>

    <script src="{{ asset(mix('vendors/js/tables/datatable/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.bootstrap5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.responsive.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/responsive.bootstrap4.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.checkboxes.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.buttons.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/jszip.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/pdfmake.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/vfs_fonts.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.html5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.print.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/datatables/buttons.server-side.js')) }}"></script>
@endsection

@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/tabs.js')) }}"></script>
    <script>
        $(function() {
            // Wait for TinyMCE to be loaded before initializing
            function initializeTinyMCE() {
                if (typeof tinymce === 'undefined') {
                    // TinyMCE not loaded yet, wait a bit and try again
                    setTimeout(initializeTinyMCE, 100);
                    return;
                }

                // Initialize TinyMCE for notes (replaces CKEditor for spell checking support)
                if (typeof initTinyMCE === 'function') {
                    initTinyMCE('content-tinymce', {
                        plugins: 'lists wordcount link code',
                        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                        height: 500,
                        menubar: false,
                        branding: false,
                        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                        browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
                    });
                } else {
                    // Fallback if initTinyMCE is not available
                    tinymce.init({
                        selector: '.content-tinymce',
                        plugins: 'lists wordcount link code',
                        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                        height: 500,
                        menubar: false,
                        branding: false,
                        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                        browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
                    });
                }
            }

            // Start initialization
            initializeTinyMCE();

            let url = document.URL;
            let hash = url.substring(url.indexOf('#'));

            $("#tabs").find("li a").each(function(key, val) {
                // console.log(hash, $(val).attr('id'));
                if (hash == $(val).attr('id')) {
                    $('#' + $(val).attr('id')).click();
                }

                $(val).click(function(ky, vl) {
                    location.hash = $(this).attr('id');
                });
            });


            let tabhash = window.location.hash;
            if (tabhash) {
                $(tabhash).tab('show');
                $(tabhash).trigger('click');
            }
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;

            myDataTable.initDefaults(defaults);

        });
    </script>

@endsection
