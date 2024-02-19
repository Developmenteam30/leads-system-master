@component('mail::message')
<p>Dear, {{ $name }}.</p>

<h2>Welcome to the {{ config('app.name') }} dashboard!</h2>

<p>This dashboard will be used for any PTO and vacation requests. HR will schedule a training on how to use the system. Please be sure to sign in ASAP so you are ready for the training session.</p>

<p>Your username is your email address: {{ $email }}</p>

<p>Please click on the button below to set your password.</p>

@component('mail::button', ['url' => $url])
Set Password
@endcomponent

<p>Thank you,</p>
<p>{{ config('app.name') }}<p>
@endcomponent
