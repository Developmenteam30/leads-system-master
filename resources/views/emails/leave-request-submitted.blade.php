@component('mail::message')
<h1>Leave Request Submitted</h1>

<p><strong>Agent:</strong> {{ $request->agent->agent_name }} ({{ $request->agent->id }})</p>

@if($request->type)
<p><strong>Type:</strong> {{ $request->type->name }}</p>
@endif

@if($request->end_time && $request->start_time != $request->end_time)
<p><strong>Start Date:</strong> {{ $request->formattedStartDate }} {{ $request->formattedStartTime }}</p>
<p><strong>End Date:</strong> {{ $request->formattedEndDate }} {{ $request->formattedEndTime }}</p>
@else
<p><strong>Date:</strong> {{ $request->formattedStartDate }}</p>
@endif

@if($request->reason)
<p><strong>Reason:</strong> {{ $request->reason->name }}</p>
@endif

<p><strong>Notes:</strong></p>
<p>{{ $request->notes }}</p>

@endcomponent
