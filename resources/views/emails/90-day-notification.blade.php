@component('mail::message')
<h1>Agents with a 90-day anniversary next week</h1>

<p>Week of {{ $next_week_monday->format('n/j/Y') }} to {{ $next_week_sunday->format('n/j/Y') }}</p>

@if(!empty($agents) && $agents->count())
<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>Agent Name</strong></td>
<td><strong>Start Date</strong></td>
<td><strong>Campaign</strong></td>
</tr>
@foreach ($agents as $agent)
<tr>
<td>{{ $agent->agent_name }}</td>
<td>{{ $agent->start_date }}</td>
<td>{{ $agent->product->name }}</td>
</tr>
@endforeach
</table>
@else
<p>No agents</p>
@endif

@endcomponent


