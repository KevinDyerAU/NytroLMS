@if (!empty($data))

    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card py-1 bg-light-info">
            <div class="card-header flex-row align-items-start pb-0">
                <div class='col-2'>
                    <div class="avatar bg-primary p-50 m-0">
                        <a href="{{ route('account_manager.companies.index') }}" class="avatar-content">
                            <i data-lucide="filter" class="font-medium-5 text-info"></i>
                        </a>
                    </div>
                </div>
                <div class='col-8 d-flex flex-row align-items-center align-self-center'>
                    <h3 class="card-text flex-wrap pe-1">Sites</h3>
                    <h2 class="fw-bolder ms-1 ">{{ $data['count'] }}</h2>
                </div>
            </div>
            <div class="card-body">
                <div class='col-12 d-flex flex-row align-items-center align-self-center mt-2'>
                    <ul class="list-group list-style-inside full-width">
                        @foreach ($data['list'] as $company)
                            <li
                                class="list-group-item bg-light-{{ \Arr::random(['warning', 'secondary', 'primary', 'danger']) }}">
                                <a href="{{ route('reports.admins.index', ['company' => $company['id']]) }}"
                                    class="stretched-link link-primary  d-flex flex-row">
                                    <span class="flex-grow-1">{{ $company['name'] }}</span>
                                    <strong class="align-self-end">{{ count($company['associated_students']) }}</strong>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <script>
            (function(window, document, $) {
                $(window).on('load', function() {

                });
            })(window, document, jQuery);
        </script>
    </div>
@endif
