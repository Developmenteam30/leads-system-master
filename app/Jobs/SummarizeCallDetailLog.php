<?php

namespace App\Jobs;

use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentType;
use App\Models\DialerDispositionLog;
use App\Models\DialerLog;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SummarizeCallDetailLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 1;

    protected $date;
    protected $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $email, $logId = null)
    {
        $this->date = $date;
        $this->email = $email;
        $this->logId = $logId;
        $this->subject = 'Summarize Call Detail Logs: '.$this->date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            // Clear out existing records
            $rows = DialerAgentPerformance::query()
                ->whereDate('dialer_agent_performances.file_date', $this->date)
                ->get();

            $rows->each(function ($row) {
                DialerAgentPerformance::updateOrCreate([
                    'agent_id' => $row->agent_id,
                    'file_date' => $this->date,
                ], [
                    'transfers' => null,
                    'under_5_min' => null,
                    'under_6_min' => null,
                    'over_7_min' => null,
                    'over_20_min' => null,
                    'over_60_min' => null,
                    'bonus_amount' => null,
                    'bonus_rate' => null,
                    'failed_transfers' => null,
                    'billable_transfers' => null,
                    'billable_transfers_bill_time' => null,
                    'successful_transfers_bill_time' => null,
                ]);
            });

            // 7315: Lowering the sale criteria to 55 minutes
            if (Carbon::parse($this->date)->startOfDay()->gte(Carbon::parse('2023-05-15')->startOfDay())) {
                $seconds = 60 * 55;
            } else {
                $seconds = 60 * 60;
            }

            $rows = DialerLog::query()
                ->join('dialer_external_campaigns', 'dialer_external_campaigns.id', 'dialer_logs.campaign_id')
                ->select([
                    'agent_id',
                    'dialer_external_campaigns.campaign_id',
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') THEN 1 ELSE 0 END) AS transfers"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') AND TIME_TO_SEC(bill_time) < 300 THEN 1 ELSE 0 END) AS under_5_min"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') AND TIME_TO_SEC(bill_time) < 360 THEN 1 ELSE 0 END) AS under_6_min"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') AND TIME_TO_SEC(bill_time) >= 420 THEN 1 ELSE 0 END) AS over_7_min"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') AND TIME_TO_SEC(bill_time) >= 1200 THEN 1 ELSE 0 END) AS over_20_min"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') AND TIME_TO_SEC(bill_time) >= {$seconds} THEN 1 ELSE 0 END) AS over_60_min"),
                    DB::raw("SUM(CASE WHEN status IN ('TFCB') THEN 1 ELSE 0 END) AS failed_transfers"),
                    DB::raw("SUM(CASE WHEN status IN ('ST', 'STSSDI') THEN TIME_TO_SEC(bill_time) ELSE 0 END) AS successful_transfers_bill_time"),
                ])
                ->timestampQuery($this->date)
                ->whereNotNull('agent_id')
                ->groupBy('agent_id', 'dialer_external_campaigns.campaign_id')
                ->get();

            $rows->each(function ($row) {
                DialerAgentPerformance::updateOrCreate([
                    'agent_id' => $row->agent_id,
                    'file_date' => $this->date,
                    'internal_campaign_id' => $row->campaign_id,
                ], [
                    'transfers' => $row->transfers,
                    'under_5_min' => $row->under_5_min,
                    'under_6_min' => $row->under_6_min,
                    'over_7_min' => $row->over_7_min,
                    'over_20_min' => $row->over_20_min,
                    'over_60_min' => $row->over_60_min,
                    'failed_transfers' => $row->failed_transfers,
                    'successful_transfers_bill_time' => $row->successful_transfers_bill_time,
                ]);
            });

            // 6545: Calculate billable transfer duration
            $billableTransfers = [];

            // Only return the 3-way call with the longest bill_time
            $groupedLeads = DialerLog::query()
                ->select([
                    '*',
                    DB::raw('row_number() OVER (PARTITION BY lead_id ORDER BY bill_time DESC) AS rank_num'),
                ])
                ->timestampQuery($this->date)
                ->where('status', 'TWCE');

            $rows = DB::query()->fromSub($groupedLeads, 'x')
                ->where('rank_num', 1)
                ->get();

            $rows->each(function ($row) use (&$billableTransfers) {
                $startDate = Carbon::parse($row->time_stamp)->subHours(5);
                $endDate = Carbon::parse($row->time_stamp);
                $fileDate = Carbon::parse($row->time_stamp)->format('Y-m-d');

                // 7315: Changing BT formula to 390 seconds
                // 7371: Changing BT formula to 330 seconds.
                if (Carbon::parse($this->date)->startOfDay()->gte(Carbon::parse('2023-05-15')->startOfDay())) {
                    $seconds = 330;
                } else {
                    $seconds = 123;
                }

                $transfer = DialerLog::query()
                    ->join('dialer_external_campaigns', 'dialer_external_campaigns.id', 'dialer_logs.campaign_id')
                    ->select([
                        'agent_id',
                        'dialer_external_campaigns.campaign_id',
                        DB::raw("TIME_TO_SEC(bill_time) AS bill_time"),
                    ])
                    ->where('lead_id', $row->lead_id)
                    ->whereBetween('time_stamp', [
                        $startDate,
                        $endDate,
                    ])
                    ->where(DB::raw('TIME_TO_SEC(bill_time)'), '>', $seconds)
                    ->where('agent_name', 'NOT LIKE', 'System%')
                    ->whereIn('status', ['ST', 'TFCB', 'STSSDI'])
                    ->whereNotNull('agent_id')
                    ->orderBy('time_stamp', 'DESC')
                    ->first();

                if (!empty($transfer)) {
                    if (isset($billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['count'])) {
                        $billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['count']++;
                    } else {
                        $billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['count'] = 1;
                    }
                    if (isset($billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['time'])) {
                        $billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['time'] += $transfer->bill_time;
                    } else {
                        $billableTransfers[$transfer->agent_id][$transfer->campaign_id][$fileDate]['time'] = $transfer->bill_time;
                    }
                } else {
                    //Log::error("SummarizeCallDetailLog: Missing ST {$row->call_id}");
                }
            });

            foreach ($billableTransfers as $agent_id => $campaigns) {
                foreach ($campaigns as $campaign_id => $dates) {
                    foreach ($dates as $date => $value) {
                        DialerAgentPerformance::updateOrCreate([
                            'agent_id' => $agent_id,
                            'file_date' => $date,
                            'internal_campaign_id' => $campaign_id,
                        ], [
                            'billable_transfers' => $value['count'],
                            'billable_transfers_bill_time' => $value['time'],
                        ]);
                    }
                }
            }

            $fileDate = Carbon::parse($this->date);

            if ($fileDate->lt(Carbon::parse("2023-01-01 00:00:00"))) {
                // 6356: Daily contest for number of transfers

                // Find the top 3 transfer values, ignoring ties
                $transfers = DialerAgentPerformance::query()
                    ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
                    ->joinEffectiveDates()
                    ->select('billable_transfers')
                    ->whereDate('dialer_agent_performances.file_date', $this->date)
                    ->whereIn('dialer_agents.company_id', DialerAgent::PAYROLL_COMPANY_IDS)
                    ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
                    ->where('billable_transfers', '>=', 12)
                    ->orderBy('billable_transfers', 'DESC')
                    ->distinct()
                    ->limit(3)
                    ->get();

                Log::debug("SummarizeCallDetailLog: Top 3 transfers {$transfers}");

                foreach ($transfers as $transfer) {
                    if (empty($transfer->billable_transfers)) {
                        continue;
                    }

                    $rows = DialerAgentPerformance::query()
                        ->whereDate('dialer_agent_performances.file_date', $this->date)
                        ->where('billable_transfers', $transfer->billable_transfers)
                        ->get();

                    foreach ($rows as $row) {
                        if ($row->billable_transfers == $transfers[0]->billable_transfers) {
                            Log::debug("SummarizeCallDetailLog: $10.00 bonus for {$row->agent_id} {$row->billable_transfers}");
                            $row->bonus_amount += 10;
                        } elseif (isset($transfers[1]->billable_transfers) && $row->billable_transfers == $transfers[1]->billable_transfers) {
                            Log::debug("SummarizeCallDetailLog: $7.50 bonus for {$row->agent_id} {$row->billable_transfers}");
                            $row->bonus_amount += 7.50;
                        } elseif (isset($transfers[2]->billable_transfers) && $row->billable_transfers == $transfers[2]->billable_transfers) {
                            Log::debug("SummarizeCallDetailLog: $5.00 bonus for {$row->agent_id} {$row->billable_transfers}");
                            $row->bonus_amount += 5.00;
                        }
                        $row->save();
                    }
                }
            } elseif ($fileDate->gte(Carbon::parse("2023-01-01 00:00:00")) && $fileDate->lt(Carbon::parse("2023-05-15 00:00:00"))) {
                // 6718: New transfer contest logic effective 1/2/23
                // 7371: As of 5/15/23, there is no more bonus contest.
                // These are broken into two groups of winners based on the alphabet
                $groups = [
                    [
                        'lower' => 'A',
                        'upper' => 'K',
                    ],
                    [
                        'lower' => 'L',
                        'upper' => 'Z',
                    ],
                ];

                foreach ($groups as $group) {
                    // Find the top 3 transfer values, ignoring ties
                    $transfers = DialerAgentPerformance::query()
                        ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
                        ->joinEffectiveDates()
                        ->select('billable_transfers')
                        ->whereDate('dialer_agent_performances.file_date', $this->date)
                        ->whereIn('dialer_agents.company_id', DialerAgent::PAYROLL_COMPANY_IDS)
                        ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
                        ->whereBetween(DB::raw("LEFT(REGEXP_REPLACE(UPPER(dialer_agents.agent_name),'[^A-Z]', ''),1)"), [
                            $group['lower'],
                            $group['upper'],
                        ])
                        ->where('billable_transfers', '>=', 12)
                        ->orderBy('billable_transfers', 'DESC')
                        ->distinct()
                        ->limit(3)
                        ->get();

                    Log::debug("SummarizeCallDetailLog {$group['lower']}-{$group['upper']}: Top 3 transfers {$transfers}");

                    foreach ($transfers as $transfer) {
                        if (empty($transfer->billable_transfers)) {
                            continue;
                        }

                        $rows = DialerAgentPerformance::query()
                            ->whereDate('dialer_agent_performances.file_date', $this->date)
                            ->where('billable_transfers', $transfer->billable_transfers)
                            ->get();

                        foreach ($rows as $row) {
                            if ($row->billable_transfers == $transfers[0]->billable_transfers) {
                                Log::debug("SummarizeCallDetailLog: $7.50 bonus for {$row->agent_id} {$row->billable_transfers}");
                                $row->bonus_amount += 7.50;
                            } elseif (isset($transfers[1]->billable_transfers) && $row->billable_transfers == $transfers[1]->billable_transfers) {
                                Log::debug("SummarizeCallDetailLog: $5.00 bonus for {$row->agent_id} {$row->billable_transfers}");
                                $row->bonus_amount += 5.00;
                            } elseif (isset($transfers[2]->billable_transfers) && $row->billable_transfers == $transfers[2]->billable_transfers) {
                                Log::debug("SummarizeCallDetailLog: $5.00 bonus for {$row->agent_id} {$row->billable_transfers}");
                                $row->bonus_amount += 5.00;
                            }
                            $row->save();
                        }
                    }
                }
            }

            // Update disposition stats
            $rows = DialerLog::query()
                ->join('dialer_statuses', 'dialer_statuses.status', 'dialer_logs.status')
                ->select([
                    'dialer_logs.agent_id',
                    DB::raw("dialer_statuses.id AS status_id"),
                    DB::raw("COUNT(*) AS total"),
                ])
                ->timestampQuery($this->date)
                ->whereNotNull('dialer_logs.agent_id')
                ->groupBy([
                    'dialer_logs.agent_id',
                    'dialer_logs.status',
                ])
                ->get();

            $rows->each(function ($row) {
                DialerDispositionLog::updateOrCreate([
                    'agent_id' => $row->agent_id,
                    'file_date' => $this->date,
                    'status_id' => $row->status_id,
                ], [
                    'total' => $row->total,
                ]);
            });

            $this->markLogAsSuccess();

            DB::commit();

            //Mail::to($this->email)->send(new JobStatus(self::class, 'Success', ''));
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
