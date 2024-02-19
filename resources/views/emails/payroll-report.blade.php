@component('mail::message')
<h1>Payroll Report</h1>

@if($user && $user->agent_name)
<p><strong>Sent By:</strong> {{ $user->agent_name }}</p>
@endif

@if($campaign)
<p><strong>Campaign:</strong> {{ $campaign->name }}</p>
@endif

<p><strong>Start Date:</strong> {{ $startDate }}</p>

<p><strong>End Date:</strong> {{ $endDate }}</p>

@endcomponent
