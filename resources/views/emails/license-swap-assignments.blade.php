@component('mail::message')
<h1>License Swap Reassignments</h1>

<p>Please reassign the following licenses:</p>

<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>IN</strong></td>
<td><strong>OUT</strong></td>
</tr>
@foreach ($licenses as $license)
<tr>
<td>{{ $license['to']['name'] }}</td>
<td>{{ $license['from']['name'] }}</td>
</tr>
@endforeach
</table>

@endcomponent
