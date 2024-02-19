@component('mail::message')
<h1>Welcome to {{ config('app.name') }}</h1>

<p>Please click on the button below to set your {{ config('app.name') }} password.</p>

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

@endcomponent
