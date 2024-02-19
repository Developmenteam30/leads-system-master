@component('mail::message')
<h1>Write-Up Notification</h1>

<p><strong>Agent:</strong> {{ $writeup->agent->agent_name }}</p>

<p><strong>Date:</strong> {{ $writeup->date }}</p>

<p><strong>Submitted By:</strong> {{ $writeup->reporter->agent_name }}</p>

@if($writeup->level)
<p><strong>Level:</strong> {{ $writeup->level->name }}</p>
@endif

@if($writeup->reason)
<p><strong>Reason:</strong> {{ $writeup->reason->reason }}</p>
@endif

<p><strong>Notes:</strong></p>
<p>{{ $writeup->notes }}</p>

@endcomponent
