@component('mail::message')
<h1>License Swap Report</h1>

<p><strong>Date:</strong> {{ $date }}</p>

<h3>Licenses Available to be Reassigned ({{ count($data['available_licenses']) }})</h3>
@forelse ($data['available_licenses'] as $agent)
{{ $agent['name'] }}<br/>
@empty
<p>No licenses available</p>
@endforelse

@endcomponent
