@component('mail::message')
<h1>Daily Stats Report</h1>

<p><strong>Date:</strong> {{ $date }}</p>

<div class="table">
<table class="table-bordered table-padded">
<tr>
<td>&nbsp;</td>
<td class="text-center"><strong>Agents</strong></td>
<td class="text-center"><strong>Employees</strong></td>
</tr>
<tr>
<td><strong>Billable Hours</strong></td>
<td class="text-right">{{ $stats['agent_billable_hours'] }}</td>
<td class="text-right">{{ $stats['employee_billable_hours'] }}</td>
</tr>
<tr>
<td><strong>Avg Rate</strong></td>
<td class="text-right">{{ $stats['agent_billable_rate'] }}</td>
<td class="text-right">{{ $stats['employee_billable_rate'] }}</td>
</tr>
<tr>
<td><strong>Total</strong></td>
<td class="text-right">{{ $stats['agent_billable_total'] }}</td>
<td class="text-right">{{ $stats['employee_billable_total'] }}</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td><strong>Payable Hours</strong></td>
<td class="text-right">{{ $stats['agent_payable_hours'] }}</td>
<td class="text-right">{{ $stats['employee_payable_hours'] }}</td>
</tr>
<tr>
<td><strong>Avg Rate</strong></td>
<td class="text-right">{{ $stats['agent_payable_rate'] }}</td>
<td class="text-right">{{ $stats['employee_payable_rate'] }}</td>
</tr>
<tr>
<td><strong>Total</strong></td>
<td class="text-right">{{ $stats['agent_payable_total'] }}</td>
<td class="text-right">{{ $stats['employee_payable_total'] }}</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td><strong>Profit</strong></td>
<td class="text-right">{{ $stats['agent_gross_profit'] }}</td>
<td class="text-right">{{ $stats['employee_gross_profit'] }}</td>
</tr>
</table>
</div>
@endcomponent
