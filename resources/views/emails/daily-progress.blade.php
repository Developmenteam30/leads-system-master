@component('mail::message')
<h1>Daily Progress Report</h1>

<p><strong>Week:</strong> {{ $data['startDate']->format('m/d/Y') }} - {{ $data['endDate']->format('m/d/Y') }}</p>

<hr class="spaced"/>

<h2>Weekly Evaluation Stats</h2>
<div class="table">
<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>Rank</strong></td>
<td><strong>Team</strong></td>
<td><strong>Agents</strong></td>
<td><strong>Eval Count</strong></td>
<td><strong>Eval Percent</strong></td>
</tr>
@foreach($data['stats']->sortByDesc('evaluations_completed_percent') as $team)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $team['team']->name }}</td>
<td>{{ $team['active_agents'] }}</td>
<td>{{ $team['evaluations_completed_count'] }}</td>
<td @class([
        'report-success' => $team['evaluations_completed_percent'] > $team['evaluations_expected_percent'],
        'report-error' => $team['evaluations_completed_percent'] < $team['evaluations_expected_percent'],
])>{{ $team['evaluations_completed_percent'] }}</td>
</tr>
@endforeach
</table>
</div>

<h4>Evaluations Completed Today</h4>
<ul>
@forelse ($data['evaluations_completed'] as $evaluation)
<li>{{ $evaluation->agent->agent_name }} - {{ $evaluation->agent?->team?->name }} ({{ $evaluation->reporter->agent_name }})</li>
@empty
<li>No evaluations were completed today.</li>
@endforelse
</ul>

<hr class="spaced"/>

<h2>Weekly Write-Up Stats</h2>
<div class="table">
<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>Team</strong></td>
<td><strong>Count</strong></td>
</tr>
@foreach($data['stats']->sortBy('team.name') as $team)
<tr>
<td>{{ $team['team']->name }}</td>
<td>{{ $team['writeups_completed_count'] }}</td>
</tr>
@endforeach
</table>
</div>

<h4>Write-Ups Entered Today</h4>
<ul>
@forelse ($data['writeups_completed'] as $writeup)
<li>{{ $writeup->agent->agent_name }} - {{ $writeup->agent?->team?->name }} ({{ $writeup->reporter->agent_name }})</li>
@empty
<li>No write-ups were entered today.</li>
@endforelse
</ul>

@endcomponent
