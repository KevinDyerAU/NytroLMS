@component('mail::message')
    @component('mail::panel')
        This is test Email sent: to "{{ $to->email }}" and cc "{{ $cc }}"
    @endcomponent
    The develper is testing.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
