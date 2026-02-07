<!-- BEGIN: Vendor JS-->
<script src="{{ asset(mix('vendors/js/vendors.min.js')) }}"></script>
<!-- BEGIN Vendor JS-->
<!-- BEGIN: Page Vendor JS-->
<script src="{{ asset(mix('vendors/js/ui/jquery.sticky.js')) }}"></script>
@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
<script src="{{ asset(mix('js/core/app-menu.js')) }}"></script>
<script src="{{ asset(mix('js/core/app.js')) }}"></script>
<!-- custome scripts file for user -->
<script src="{{ asset(mix('js/core/scripts.js')) }}"></script>
<script src="{{ asset(mix('js/scripts/ui/lucide.min.js')) }}"></script>
<script src="{{ asset(mix('js/scripts/_my/lucide-init.js')) }}"></script>

<!-- Idle Timeout Tracker -->
@auth
    <script>
        // Pass Laravel config to JavaScript
        window.idleTimeoutConfig = {
            timeoutMinutes: {{ config('idle-timeout.timeout_minutes', 120) }},
            warningMinutes: {{ config('idle-timeout.warning_minutes', 5) }},
            checkIntervalMs: {{ config('idle-timeout.check_interval_ms', 60000) }},
            updateIntervalMs: {{ config('idle-timeout.update_interval_ms', 300000) }}
        };
    </script>
    <script src="{{ asset(mix('js/scripts/_my/idle-timeout.js')) }}"></script>
@endauth

@if ($configData['blankPage'] === false)
    <script src="{{ asset(mix('js/scripts/customizer.js')) }}"></script>
@endif
<!-- END: Theme JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
<!-- Lucide initialization is now handled by lucide-init.js -->
<!-- END: Lucide Icons -->

<script>
    let autocomplete;

    function initAutoCompleteAddress() {
        let inputs = document.querySelectorAll(".autocomplete-address");
        // console.log(inputs);
        const options = {
            types: ["address"],
            componentRestrictions: {
                country: "AU"
            },
            fields: ["address_components", "formatted_address"]
        };
        $.each(inputs, function(index, input) {
            // let field = $(this);
            autocomplete = new google.maps.places.Autocomplete(
                input,
                options
            );
            autocomplete.addListener("place_changed", function() {
                const place = autocomplete.getPlace();
                // console.log(index, input, field, place.formatted_address);
                if (typeof place != 'undefined') {
                    $(input).val(place.formatted_address);
                }
            });
        });
    }


    let uniqueEmailError = new bootstrap.Modal(document.getElementById('uniqueEmailError'));
    @if ($errors->has('email'))
        if (!!$("#email").val()) {
            uniqueEmailError.show();
        }
    @endif
</script>
@if (env('APP_ENV') !== 'local')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY', 'AIzaSyAEN5gHLjf8UdiyXux_eLjFkzJ_xvJrNy0') }}&libraries=places&callback=initAutoCompleteAddress"
        async defer></script>
@endif
