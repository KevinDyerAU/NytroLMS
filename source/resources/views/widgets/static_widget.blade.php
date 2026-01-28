@if( $config['content'] === 'course_flyer' )
    <div style="display:inline" class="arrilot-widget-container">
        <a href="https://www.keyinstitute.com.au/our-courses/" class="avatar-content" target="_blank">
            <div class="card py-1 bg-light-info">
                <div class="card-header">
                    <div class="col-2">
                        <div class="avatar bg-light-primary p-50 m-0">
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="feather feather-external-link font-medium-5"><path
                                                d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline
                                                points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21"
                                                                                         y2="3"></line></svg>
                                    </span>
                        </div>
                    </div>
                    <div class="col-8 d-flex flex-row align-items-start align-self-start">
                        <h2 class="card-text">Course Flyer</h2>
                    </div>
                </div>
            </div>
        </a>
    </div>
@else
    <div class="col-12">
        <div style="display:inline" class="arrilot-widget-container">
            <a href="{{ route('account_manager.students.create') }}" class="avatar-content">
                <div class="card py-1 bg-light-success">
                    <div class="card-header d-flex justify-content-center align-items-center w-100">
                        <div class="col-4">
                            <div class="avatar bg-light-success p-50 m-0">
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="feather feather-user-plus font-medium-5"><path
                                                d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5"
                                                                                                             cy="7"
                                                                                                             r="4"></circle><line
                                                x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17"
                                                                                            y2="11"></line></svg>
                                    </span>
                            </div>
                        </div>
                        <div class="col-7 flex-grow-0" style="flex: auto">
                            <h2 class="card-text">Add New Student</h2>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endif
