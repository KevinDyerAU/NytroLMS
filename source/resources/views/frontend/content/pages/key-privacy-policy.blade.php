@extends('frontend.layouts.contentLayoutMaster')

@section('title', 'Privacy Policy')

@section('content')
    <div class="blog-detail-wrapper mb-5 mt-2">
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} deals with the collection, use and disclosure, storage, security, accessibility and correction
            of personal information in accordance with the Privacy Act 1988 (Cth). We are committed to protecting your
            privacy and have developed the following policy which sets out our procedures for handling personal
            information.</p>
        <h3><strong>What information do we collect?</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} collects personal information such as your name, address, telephone number(s), e-mail address.
            {{ config('settings.site.institute_name', 'Key Institute') }} does not collect any personal information, except that which is knowingly supplied by the
            individual or information that is otherwise publicly available.</p>
        <h3><strong>How do we use your personal information?</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} uses the information collected for the primary purpose of providing you with information and
            material about our campaigns/events and for reasonably incidental secondary purposes. If at any time, you do
            not wish to receive further electronic messages from {{ config('settings.site.institute_name', 'Key Institute') }}, please send a blank email to <a
                href="mailto:{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}">{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}</a> and enter ‘unsubscribe’ in the
            subject line. {{ config('settings.site.institute_name', 'Key Institute') }} will not provide your personal information to third parties without first
            obtaining your consent.</p>
        <h3><strong>Do we collect sensitive information?</strong></h3>
        <p>‘Sensitive information’ includes but is not limited to information or an opinion about an individual’s
            political opinions, membership of a political association and religious or philosophical beliefs. Key
            Institute will only collect sensitive information with your consent (unless it is required to do so by law)
            and for the express purpose for which the information was provided or for a reasonably incidental secondary
            purpose.</p>
        <h3><strong>How do we ensure that your personal information is accurate?</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} will use its best endeavours to ensure that your personal information is accurate, complete and
            up to date.</p>
        <h3><strong>How is your personal information secured?</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} understands the importance of protecting your personal information from misuse, loss,
            modification or disclosure. Access to your personal information is therefore restricted to authorised staff
            of {{ config('settings.site.institute_name', 'Key Institute') }} and its related or associated entities. You are entitled to access the personal information
            which {{ config('settings.site.institute_name', 'Key Institute') }} holds about you by calling us on {{ config('settings.site.institute_phone', '1300 471 660') }} and asking for our Privacy Officer.
            However, we reserve the right to reasonably refuse access to that information on the basis of the exemptions
            set out in the Privacy Act.</p>
        <h3><strong>Third parties</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} may at its discretion use third parties to provide essential services to our website or to
            assist with our events and programs. We may share your personal details in order to facilitate delivery of
            those services, events or programs. Third parties are prohibited from using your personal information for
            any other purpose.</p>
        <h3><strong>Legal</strong></h3>
        <p>{{ config('settings.site.institute_name', 'Key Institute') }} reserves the right to disclose your personal information as required by law and when we believe
            the disclosure is necessary to protect our rights or to comply with a judicial proceeding, court order or
            other legal process.</p>
        <h3><strong>Updating your information</strong></h3>
        <p>If you wish to modify any information that you have previously given us, or if you want to opt out of future
            communications please contact:</p>
        <p>Level 4, 99 Queensbridge St, Southbank, Victoria 3006 <br>{{ config('settings.site.institute_phone', '1300 471 660') }}<br><a
                href="mailto:{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}" target="_blank"
                style="font-family: Hind, sans-serif; font-weight: 400; font-size: 1.25rem;">{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}</a>
        </p>
        <h3><strong>Availability of policy</strong></h3>
        <p>This policy is available upon request. It may be reviewed and updated due to legislative changes or changes
            in our organisational structure or objectives.</p>
        <h3><strong>Contacting us about our privacy policy</strong></h3>
        <p>For further information about the privacy policy contact our Privacy Officer using the contact information
            provided above.</p>
        <h3><strong>Web Browser Cookies</strong></h3>
        <p>Our website may use “cookies” to enhance User experience. User’s web browser places cookies on their hard
            drive for record-keeping purposes and sometimes to track information about them. User may choose to set
            their web browser to refuse cookies, or to alert you when cookies are being sent. If they do so, note that
            some parts of the Site may not function properly.</p>
    </div>
@endsection
