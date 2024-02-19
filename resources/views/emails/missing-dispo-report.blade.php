@component('mail::message')
<h1>Missing Disposition Report</h1>

<p><strong>Date:</strong> {{ $date }}</p>

<p>The following disposition files were not uploaded in the past 2 weeks:</p>
@foreach ($dates as $date)
<p>{{ $date }}</p>
@endforeach

@endcomponent
