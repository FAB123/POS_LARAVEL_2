@component('mail::message')
# Hello Sir,

This is the email from {{$message['company']}}.

{{ $message['message'] }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
