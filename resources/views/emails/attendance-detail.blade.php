@component('mail::message')
<h1>Attendance Detail Update</h1>

<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>Agent Name</strong></td>
<td><strong>First Call Time</strong></td>
<td><strong>Last Call Time</strong></td>
<td><strong>Status</strong></td>
</tr>
@foreach ($agents as $agent)
<tr>
<td>{{ $agent['agent_name'] }}</td>
<td>{{ $agent['first_call_time'] }}</td>
<td>{{ $agent['last_call_time'] }}</td>
<td>{{ $agent['status'] }}</td>
</tr>
@endforeach
</table>

@endcomponent
