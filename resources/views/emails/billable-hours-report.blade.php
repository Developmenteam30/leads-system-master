@component('mail::message')
<h1>Billable Hours Report</h1>

@if($company)
<p><strong>Client:</strong> {{ $company->name }}</p>
@endif

<p><strong>Start Date:</strong> {{ $startDate }}</p>

<p><strong>End Date:</strong> {{ $endDate }}</p>

@endcomponent
