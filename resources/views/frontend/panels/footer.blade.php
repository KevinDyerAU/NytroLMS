<!-- BEGIN: Footer-->
<div class="row clearfix bg-light-primary text-secondary p-2 mt-3">
    <div class="col-md-3">

    </div>
    <div class="col-md-3">
        <h5 class="mb-2">Contact Us</h5>
        <p>{{ config('settings.site.institute_phone', '1300 471 660') }}</p>
        <p>{{ config('settings.site.institute_email', 'admin@keycompany.com.au') }}</p>
        <p>Mon - Fri 9am- 6:30pm</p>
    </div>
    <div class="col-md-3">
        <h5 class="mb-2">Support</h5>
        @php
            $footerMenu = $settings['footer'] ?? null;
        @endphp

        @if (!empty($footerMenu))
            <ul class="list-unstyled">
                @foreach ($footerMenu as $menu)
                    <li>
                        <a href="{{ isset($menu['link']) ? url($menu['link']) : 'javascript:void(0)' }}"
                            class="footer-link d-block pb-50" target="{{ isset($menu['target']) ? '_blank' : '_self' }}">
                            <span class="menu-title text-truncate">{{ __($menu['title']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            @if (env('SETTINGS_KEY') === 'KeyInstitute')
                <p class=" d-flex flex-column">
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Student-Handbook-2024.pdf"
                        download>Student Handbook</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/Complaints-and-Appeals-Policy-and-Procedure.pdf"
                        download>Complaints and Appeals Policy and Procedure</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Statement-of-Fees-2024.pdf"
                        download>Statement of Fees 2024</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/Student-Fees-and-Charges-Policy.pdf"
                        download>Student Fees and Charges Policy</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/VET-Data-Privacy-Policy.pdf"
                        download>Data Privacy Policy</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Information-about-Online-Learning.pdf"
                        download>Information about Online Learning</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Subsidised-Training-and-Fee-for-Service-Information.pdf"
                        download>Subsidised Training and Fee for Service Information</a>
                    <a target="_blank"
                        href="https://www.keyinstitute.com.au/wp-content/uploads/2024/10/Key-Institute-Third-Party-Arrrangements.pdf"
                        download>Third-Party Arrangements</a>
                    <a target="_blank" href="{{ route('cms', ['page' => 'privacy_policy']) }}">Privacy Policy</a>
                </p>
            @else
                <p class=" d-flex flex-column">
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/09/Student-Handbook-2024.pdf"
                        download>Student Handbook</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/06/Statement-of-Fees-2024.pdf"
                        download>Statement of Fees 2024</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/06/VET-Data-Privacy-Policy.pdf"
                        download>Data Privacy Policy</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/06/Complaints-and-Appeals-Policy-and-Procedure.pdf"
                        download>Complaints and Appeals Policy and Procedure</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/06/Student-Fees-and-Charges-Policy.pdf"
                        download>Student Fees and Charges Policy</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/07/Information-about-Online-Learning.pdf"
                        download>Information about Online Learning</a>
                    <a target="_blank"
                        href="https://knowledgespace.com.au/wp-content/uploads/2024/10/KnowledgeSpace-Third-Party-Arrangements.pdf"
                        download>Third-Party Arrangements</a>
                    <a target="_blank" href="{{ route('cms', ['page' => 'privacy_policy']) }}">Privacy Policy</a>
                </p>
            @endif

        @endif
    </div>
</div>

<footer
    class="footer footer-light {{ $configData['footerType'] === 'footer-hidden' ? 'd-none' : '' }} {{ $configData['footerType'] }}">

    <p class="clearfix">
        <span class="float-md-start d-block d-md-inline-block mt-25">COPYRIGHT &copy;
            <script>
                document.write(new Date().getFullYear())
            </script><a class="ms-25" href="{{ env('APP_URL') }}"
                target="_blank">{{ env('APP_NAME') }}</a>,
            <span class="d-none d-sm-inline-block">All rights Reserved</span>
        </span>
        <span class="float-md-end d-none d-md-block">Developed by <a href="https://www.inceptionsol.com"
                title="Web Solution Developer">InceptionSol</a></span>
    </p>
</footer>
<button class="btn btn-primary btn-icon scroll-top" type="button"><i data-lucide="arrow-up"></i></button>
<!-- END: Footer-->
@if (!empty($settings['featured_images']['student']['image']))
    <div class="modal fade" id="studyClubModal" tabindex="-1" aria-labelledby="studyClubModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studyClubModalLabel">
                        {{ $settings['featured_images']['student']['title'] ?? config('settings.site.institute_name', 'Key Institute') . ' - Study Club' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <a href="{{ $settings['featured_images']['student']['link'] ?? '#' }}"
                        title="{{ $settings['featured_images']['student']['title'] ?? config('settings.site.institute_name', 'Key Institute') . ' - Study Club' }}"
                        target="_blank">
                        <img src="{{ asset($settings['featured_images']['student']['image']) }}"
                            class="img-fluid w-100"
                            alt="{{ $settings['featured_images']['student']['title'] ?? '' }}" />
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
