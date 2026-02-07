<div class="modal fade modal-danger" id="uniqueEmailError" tabindex="-1" aria-labelledby="uniqueEmailErrorLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uniqueEmailErrorLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    Client already exists in the system. To add a new course to the client's existing profile,
                    please contact {{ env('APP_NAME') }} at
                    {{ config('settings.site.institute_phone', '1300 471 660') }}
                    or email <a
                        href="mailto:{{ config('settings.site.institute_email', 'admin@keycompany.com.au') }}">{{
                        config('settings.site.institute_email', 'admin@keycompany.com.au') }}</a>.
                    Thank you
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- BEGIN: Footer-->
<footer
    class="footer footer-light {{ $configData['footerType'] === 'footer-hidden' ? 'd-none' : '' }} {{ $configData['footerType'] }}">
    <p class="clearfix mb-0">
        <span class="float-md-start d-block d-md-inline-block mt-25">
            {{ config('settings.site.copyright_text', 'COPYRIGHT') }} &copy;
            <script>
                document.write(new Date().getFullYear())
            </script><a class="ms-25 d-print-none" href="{{ env('APP_URL') }}"
                target="_blank">{{ env('APP_NAME') }}</a>,
            <span class="d-none d-sm-inline-block">All rights Reserved</span>
        </span>
        <span class="float-md-end d-none d-md-block d-print-none">Developed by <a href="https://www.inceptionsol.com"
                title="Web Solution Developer">InceptionSol</a></span>
    </p>
</footer>
<button class="btn btn-primary btn-icon scroll-top d-print-none" type="button"><i data-lucide="arrow-up"></i></button>
<!-- END: Footer-->
