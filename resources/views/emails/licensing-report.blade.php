@component('mail::message')
<h1>Licensing Report</h1>

<p><strong>Date:</strong> {{ $date }}</p>

<p>Please remove the following agents from the dialer:</p>
@forelse ($agents as $agent)
<p>{{ $agent->agent_name }} ({{ $agent->latestActiveEffectiveDate->end_date }})</p>
@empty
<p>No agents</p>
@endforelse

@endcomponent
