@if(isset($studentName) && isset($date))
    <div style="width: 100%; margin-bottom: 30px; display: flex; align-items: center; justify-content: center;">
        <img src="{{ \App\Helpers\Helper::getLogoBase64() }}" alt="{{ env('APP_NAME') }}" style="height: 50px; margin-right: 15px;">
    </div>
@endif
<div class='col-lg-12 col-12' id="agreement-content">
    <div class="content-header">
        <div class="clearfix divider divider-secondary divider-start-center ">
            <span class="divider-text text-dark" style="font-size: 2rem; font-weight: bold; vertical-align: middle;">Applicant Declarations and Consent:</span>
        </div>
    </div>
    <div class="row">
        <div class="mb-1 col-md-12">
            <p>Review the Key Institute Student Handbook if available or click on this website link <a href="https://www.keyinstitute.com.au/" title="Key Institute" target="_blank">www.keyinstitute.com.au</a> and read our policies and procedures:</p>

            @php
                $footerMenu = $settings['footer'] ?? NULL;
            @endphp

            @if(!empty($footerMenu))
                <ul>
                    @foreach( $footerMenu as $menu)
                        <li>
                            <a href="{{ isset($menu['link'])? url($menu['link']):'javascript:void(0)'}}"
                               class="footer-link d-block pb-50"
                               target="{{ isset($menu['target']) ? '_blank':'_self'}}">
                                <span class="menu-title text-truncate">{{ __($menu['title']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
            <ul>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/Complaints-and-Appeals-Policy-and-Procedure.pdf" target="_blank">Complaints and Appeals Policy and Procedures</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/Student-Fees-and-Charges-Policy.pdf" target="_blank">Student Fees, Charges and Refund Policy</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Statement-of-Fees-2024.pdf" target="_blank">Statement of Fees</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/06/VET-Data-Privacy-Policy.pdf" target="_blank">Data Privacy Policy</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Student-Handbook-2024.pdf" target="_blank">Student Handbook</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Information-about-Online-Learning.pdf" target="_blank">Information about Online Learning</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/07/Subsidised-Training-and-Fee-for-Service-Information.pdf" target="_blank">Subsidised Training and Fee for Service Information</a></li>
                <li><a href="https://www.keyinstitute.com.au/wp-content/uploads/2024/10/Key-Institute-Third-Party-Arrrangements.pdf" target="_blank">Third Party Arrangements (to some full-paying online students enrolled in fee for service Business courses)</a></li>
            </ul>
            @endif

            <h2>Training and Assessment Declarations</h2>

            <p>I acknowledge and confirm that in signing below, I: (please tick)</p>
            <ul>
                <li>Declare that the information I have provided to the best of my knowledge is true and correct.</li>
                <li>Have read and understood Key Institute’s policies and procedures on the website, as follows:
                    <ul>
                        <li>Complaints and Appeals Policy and Procedures</li>
                        <li>Student Fees, Charges and Refund Policy</li>
                        <li>Statement of Fees</li>
                        <li>Data Privacy Policy</li>
                        <li>Student Handbook</li>
                        <li>Information about Online Learning</li>
                        <li>Subsidised Training and Fee for Service Information</li>
                        <li>Third Party Arrangements (fee for service Business courses)</li>
                    </ul>
                </li>
                <li>Am aware that I may be contacted by a government department and requested to participate in a NCVER survey, a government-endorsed project, audit or review, or a student survey, interview, or other questionnaire.</li>
                <li>Understand that my enrolment in this course does not guarantee that I will successfully complete this course and be issued with a qualification or statement of attainment until I meet all the requirements of the course as set out in the relevant training package or VET-accredited course and/or unit of competency.</li>
                <li>Understand that my enrolment in and/or completion of this course does not guarantee a particular employment outcome or any licensed/regulated outcome.</li>
                <li>Understand that Key Institute reserves the right to accept or reject any application for enrolment at its discretion.</li>
                <li>Understand that Key Institute reserves the right to cancel any course prior to the commencement date of the course should it deem it necessary and, in that event, shall refund all payments received as per Key Institute’s Student Fees and Charges Policy.</li>
                <li>Understand that my information may be used by Key Institute Pty Ltd and their partners for marketing and publicity purposes, and that if I do not wish this to happen, I need to inform Key Institute.</li>
                <li>Understand and agree to participate in Key Institute’s assessment activities that may include:
                    <ul>
                        <li>Written assignments, written questions</li>
                        <li>Case studies, projects</li>
                        <li>Observations, role plays, key skills simulations</li>
                        <li>Verbal assessments, virtual assessments, scenarios</li>
                        <li>Work placement assessments, third-party reports</li>
                    </ul>
                </li>
                <li>Understand and agree that if work placement is required in my course, I will work collaboratively with Key Institute’s Work Placement Team to be able to find a suitable employer and undertake mandatory required work placement hours, assessments and tasks. When working collaboratively with the Work Placement Team, I will complete required activities such as: filling-out preliminary questionnaires, completing industry checks, obtaining police clearance, working with children check clearance, attending work placement interviews and adhering to other pre-placement requirements.</li>
                <li>Understand and agree that if required I will work with the National Placement Manager to assist finding a suitable host employer to be able to complete my work placement assessments.</li>
                <li>Agree that I will not plagiarise the work of others or participate in any unauthorised collusion when completing and submitting my coursework.</li>
                <li>Agree that Key Institute will not be liable for any plagiarism or other forms of fraudulent activity or acts caused by me during the completion of my course.</li>
                <li>Agree to adhere to study schedules, where a study schedule has been applied.</li>
                <li>Agree to notify Key Institute of any change to my personal details or personal circumstances that may impact my ability to complete my studies.</li>
                <li>Consent to sharing my course progress information, which may include my personal information, completion of certificate copy, statement of attainment copy, and USI (Unique Student Identifier) with relevant parties including but not limited to Job Services Australia providers, Disability Employment Services providers, and employers.</li>
                <li>Agree that Key Institute may from time to time update terms and conditions relating to courses which shall be deemed to be acceptable by me after receiving written notice from Key Institute, including by way of electronic mail or email.</li>
                <li>Understand my Study Commitments:
                    <ul>
                        <li>I will actively start my units once they have been opened on the Learning Management System (LMS).</li>
                        <li>I may be withdrawn from my course within two months of inactivity.</li>
                        <li>I will advise Key Institute if I wish to defer from my course due to not being able to actively participate in opened units or my course schedule and training plan.</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="mb-1 col-md-12">
            <div class="form-check form-check-inline @error('agreement') is-invalid @enderror">
                <input class="form-check-input" type="checkbox" id="agreement" name='agreement'
                       value='agree'
                       @if(isset($studentName) && isset($date)) checked disabled @endif>
                <label class="form-check-label" for="agreement">I have read and agree to the Terms and
                    Conditions</label>
            </div>
            @error('agreement')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@if(isset($studentName) && isset($date))
    <div style="width: 100%; margin-top: 50px; display: flex; flex-wrap: wrap; align-items: center;">
        <div style="margin-bottom: 20px; font-size: 1rem;">
            Signed by: <strong>{{ $studentName }}</strong>
        </div>
        <div style="font-size: 1rem;">
            Date: <strong>{{ $date }}</strong>
        </div>
    </div>
@endif
