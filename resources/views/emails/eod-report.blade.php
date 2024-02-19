@component('mail::message')
<h1>End of Day Report</h1>

<div class="table">
<table class="table-bordered table-padded table-full-width">
<tr>
<td><strong>Date</strong></td>
<td><strong>Manager</strong></td>
<td><strong>Team</strong></td>
<td><strong>Total Team Count</strong></td>
<td><strong>Headcount</strong></td>
<td><strong>Shrinkage</strong></td>
<td><strong>Attendance Notes</strong></td>
<td><strong>Early Leave (name + approved-A or unapproved-U)</strong></td>
<td><strong>Day Prior Auto Fail</strong></td>
<td><strong>Day Prior Calls <89%</strong></td>
<td><strong>Completed Evaluations CRM</strong></td>
<td><strong>Agents Coached (#)</strong></td>
<td><strong>Agents on PIP (#)</strong></td>
<td><strong>% of the Team on PIP</strong></td>
<td><strong>Notes</strong></td>
</tr>
<tr>
<td>{{ $report->reportDate->format('n/j/Y') }}</td>
<td>{{ $report->managerAgent->agent_name }}</td>
<td>{{ $report->team->name }}</td>
<td>{{ $report->team_count }}</td>
<td>{{ $report->head_count }}</td>
<td>{{ ( $report->team_count / $report->head_count ) * 100 }}%</td>
<td>{{ $report->attendance_notes }}</td>
<td>{{ $report->early_leave }}</td>
<td>{{ $report->day_prior_auto_fail }}</td>
<td>{{ $report->day_prior_calls_under_89pct }}</td>
<td>{{ $report->completed_evaluations }}</td>
<td>{{ $report->agents_coached }}</td>
<td>{{ $report->agents_on_pip }}</td>
<td>{{ ( $report->agents_on_pip / $report->team_count ) * 100 }}%</td>
<td>{{ $report->notes }}</td>
</table>
</div>


@endcomponent
