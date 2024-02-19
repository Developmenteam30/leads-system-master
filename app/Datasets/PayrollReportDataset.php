<?php

namespace App\Datasets;

use App\Models\Company;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentType;
use App\Models\DialerHoliday;
use App\Models\DialerHolidayList;
use App\Models\DialerPaymentType;
use App\Models\DialerProduct;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollReportDataset
{
    public static function getWeeklyValues($filters)
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

        if (!empty($filters['agent_type_ids']) && !is_array($filters['agent_type_ids'])) {
            $filters['agent_type_ids'] = explode(',', $filters['agent_type_ids']);
        }
        if (!empty($filters['payment_type_ids']) && !is_array($filters['payment_type_ids'])) {
            $filters['payment_type_ids'] = explode(',', $filters['payment_type_ids']);
        }
        if (!empty($filters['product_ids']) && !is_array($filters['product_ids'])) {
            $filters['product_ids'] = explode(',', $filters['product_ids']);
        }
        if (!empty($filters['company_ids']) && !is_array($filters['company_ids'])) {
            $filters['company_ids'] = explode(',', $filters['company_ids']);
        }
        if (empty($filters['view'])) {
            $filters['view'] = Company::DIALER_REPORT_TYPE_PAYABLE;
        }

        $rows = DialerAgentPerformance::query()
            ->join('dialer_agents', 'dialer_agents.id', 'dialer_agent_performances.agent_id')
            ->leftJoin('dialer_products', 'dialer_products.id', 'dialer_agent_performances.internal_campaign_id')
            ->joinEffectiveDates()
            ->select([
                'dialer_agent_performances.agent_id',
                'dialer_agents.agent_name',
                'dialer_agents.company_id',
                'dialer_agent_performances.file_date',
                'dialer_agent_effective_dates.agent_type_id',
                'dialer_agent_effective_dates.payment_type_id',
                "dialer_agent_effective_dates.{$filters['view']}_rate",
                "dialer_agent_effective_dates.bonus_rate",
                "dialer_agent_effective_dates.start_date",
                'dialer_agent_effective_dates.product_id',
                DB::raw('dialer_products.id AS internal_campaign_id'),
                DB::raw('dialer_products.name AS internal_campaign_name'),
                DB::raw("CONCAT(dialer_agent_effective_dates.id,'-',dialer_products.id) AS effective_date_id"),
                DB::raw('ROUND(IFNULL(dialer_agent_performances.billable_time_override,0)/60,2) AS billable_time_override'),
                DB::raw("ROUND(IFNULL(dialer_agent_performances.huddle_time,0)/3600,2) AS huddle_hours"),
                DB::raw("ROUND(IFNULL(dialer_agent_performances.coaching_time,0)/3600,2) AS coaching_hours"),
                DB::raw("ROUND(IFNULL(dialer_agent_performances.pause_time,0)/3600,2) AS pause_hours"),
                'dialer_agent_performances.transfers',
                'dialer_agent_performances.failed_transfers',
                'dialer_agent_performances.billable_transfers',
                'dialer_agent_performances.billable_transfers_bill_time',
            ])
            ->whereBetween('file_date', [
                $filters['start_date'],
                $filters['end_date'],
            ])
            ->when(!empty($filters['company_ids']), function ($query) use ($filters) {
                $query->whereIn('dialer_agents.company_id', $filters['company_ids']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$filters['search'].'%');
            })
            ->when(!empty($filters['agent_id']), function ($query) use ($filters) {
                return $query->where('dialer_agent_performances.agent_id', $filters['agent_id']);
            })
            ->when(!empty($filters['agent_type_ids']), function ($query) use ($filters) {
                $query->whereIn('dialer_agent_effective_dates.agent_type_id', $filters['agent_type_ids']);
            })
            ->when(!empty($filters['payment_type_ids']), function ($query) use ($filters) {
                $query->whereIn('dialer_agent_effective_dates.payment_type_id', $filters['payment_type_ids']);
            })
            ->whereNotNull('dialer_agent_effective_dates.id')
            ->with([
                'agent',
                'agent.firstPerformanceDate',
                'agent.accessRole',
                'agent.effectiveDates',
                'agent.performances',
            ])
            ->get();

        $transfer_averages = DialerAgentPerformance::query()
            ->select([
                'dialer_agent_performances.file_date',
                DB::raw('SUM(billable_transfers_bill_time) AS billable_transfers_bill_time'),
                DB::raw("SUM(transfers) AS transfers"),
            ])
            ->whereBetween('file_date', [
                $filters['start_date'],
                $filters['end_date'],
            ])
            ->groupBy('dialer_agent_performances.file_date')
            ->get();

        $rows->each(function ($row) use ($filters, $transfer_averages, $rows) {
            $row->agent_name_role = $row->agent_name.(!empty($row->agent?->accessRole?->abbreviation) ? " (".$row->agent?->accessRole?->abbreviation.")" : '');
            $row->disabled = false; // Disallow editing in the UI
            $row->eligible_hours = null; // Hours eligible for overtime
            $row->file_date = CarbonImmutable::parse($row->file_date)->setTime(0, 0);
            $row->start_date = CarbonImmutable::parse($row->start_date)->setTime(0, 0);
            $row->effectiveHireDate = CarbonImmutable::parse($row->agent->effectiveHireDate)->setTime(0, 0);

            self::debugRow($row);

            self::calculateHuddle($row, $filters);
            self::debugRow($row);

            self::calculateCoaching($row, $filters);
            self::debugRow($row);

            self::calculateRawHours($row);
            self::debugRow($row);

            $trainingRow = $row->agent->getEffectiveTrainingValuesForDateRange(CarbonImmutable::parse($row->file_date)->subDays(14), $row->file_date);
            $row->trainingStartDate = $trainingRow?->start_date ? CarbonImmutable::parse($trainingRow->start_date) : null;
            $row->is_training = $trainingRow->is_training ?? false;

            if (!empty($row->trainingStartDate)) {
                $firstDialerDate = DialerAgentPerformance::query()
                    ->select('file_date')
                    ->where('file_date', '>=', $row->trainingStartDate)
                    ->where('billable_time', '>', 0)
                    ->where('agent_id', $row->agent_id)
                    ->groupBy('agent_id')
                    ->first();

//                $firstDialerDate = $row->agent->firstPerformanceDateAfterTraining($row->trainingStartDate->format('Y-m-d'));
            }

            $row->is_initial_training = !empty($firstDialerDate) && $row->file_date->between($row->trainingStartDate, Carbon::parse($firstDialerDate->file_date));

            self::calculateHourly($row, $filters);
            self::debugRow($row);

            self::calculateSalary($row, $filters);
            self::debugRow($row);

            self::calculateTraining($row, $filters);
            self::debugRow($row);

            self::calculateBreakTime($row, $filters);
            self::debugRow($row);

            self::calculateBonus($row, $filters, $transfer_averages);
            self::debugRow($row);

            return $row;
        });

        // 6356: Look for holidays where the agent didn't work.
        $dates = new Collection(new \DatePeriod($filters['start_date'], new \DateInterval('P1D'), $filters['end_date']));

        $dates->each(function ($date) use (&$rows, $filters) {
            $holiday = DialerHoliday::query()
                ->whereDate('holiday', $date)
                ->whereHas('holidayLists', function (Builder $query) {
                    $query->where('holiday_list_id', DialerHolidayList::BELIZE_ID);
                })
                ->first();

            if (!empty($holiday)) {
                $rows = $rows->each(function ($row) use ($date, $filters, $holiday, $rows) {
                    self::calculateWorkedBelizeHolidayHours($row, $date, $holiday, $filters);
                });
            }

            $holiday = DialerHoliday::query()
                ->whereDate('holiday', $date)
                ->whereHas('holidayLists', function (Builder $query) {
                    $query->where('holiday_list_id', DialerHolidayList::US_ID);
                })
                ->first();
            if (!empty($holiday)) {
                $rows = $rows->each(function ($row) use ($date, $filters, $holiday, $rows) {
                    self::calculateWorkedUSHolidayHours($row, $date, $holiday, $filters);
                });
            }
        });

        $dates->each(function ($date) use (&$rows, $filters) {
            $holiday = DialerHoliday::query()
                ->whereDate('holiday', $date)
                ->whereHas('holidayLists', function (Builder $query) {
                    $query->where('holiday_list_id', DialerHolidayList::BELIZE_ID);
                })
                ->first();

            if (!empty($holiday)) {
                self::calculateMissingBelizeHolidayHours($rows, $date, $holiday, $filters);
            }

            $holiday = DialerHoliday::query()
                ->whereDate('holiday', $date)
                ->whereHas('holidayLists', function (Builder $query) {
                    $query->where('holiday_list_id', DialerHolidayList::US_ID);
                })
                ->first();

            if (!empty($holiday)) {
                self::calculateMissingUSHolidayHours($rows, $date, $holiday, $filters);
            }
        });

        $rows->each(function ($row) use ($filters, $rows) {
            self::calculateOverUnder($row, $rows, $filters);
        });

        $rows->groupBy('agent_id')->map(function ($agent) use ($filters) {
            self::calculateOvertime($agent, $filters);
        });

        return $rows->groupBy('effective_date_id')->map(function ($dates) use ($filters, $rows) {
            $sum = (object) [];

            $sum->agent_id = $dates[0]->agent_id ?? null;
            $sum->agent_name = $dates[0]->agent_name ?? null;
            $sum->agent_name_role = $dates[0]->agent_name_role ?? null;
            $sum->agent = $dates[0]->agent ?? null;
            $sum->agent_type_id = $dates[0]->agent_type_id ?? null;
            $sum->company_id = $dates[0]->company_id ?? null;
            $sum->file_date = $dates[0]->file_date ?? null;
            $sum->start_date = $dates[0]->start_date ?? null;
            $sum->payment_type_id = $dates[0]->payment_type_id ?? null;
            $sum->product_id = $dates[0]->product_id ?? null;
            $sum->internal_campaign_name = $dates[0]->internal_campaign_name ?? null;
            $sum->internal_campaign_id = $dates[0]->internal_campaign_id ?? null;

            $sum->regular_hours = $dates->sum('regular_hours');
            $sum->regular_rate = $dates->avg('regular_rate');
            $sum->regular_amount = $dates->sum('regular_amount');

            $sum->coaching_hours = $dates->sum('coaching_hours');
            $sum->huddle_hours = $dates->sum('huddle_hours');

            $sum->training_hours = $dates->sum('training_hours');
            $sum->training_rate = $dates->avg('training_rate');
            $sum->training_amount = $dates->sum('training_amount');

            $sum->overtime_hours = $dates->sum('overtime_hours');
            $sum->overtime_rate = $dates->avg('overtime_rate');
            $sum->overtime_amount = $dates->sum('overtime_amount');

            $sum->qa_hours = $dates->sum('qa_hours');
            $sum->qa_rate = $dates->avg('qa_rate');
            $sum->qa_amount = $dates->sum('qa_amount');

            $sum->bonus_amount = $dates->sum('bonus_amount');

            $sum->holiday_hours = $dates->sum('holiday_hours');
            $sum->holiday_rate = $dates->avg('holiday_rate');
            $sum->holiday_amount = $dates->sum('holiday_amount');

            $sum->transfers = $dates->sum('transfers');
            $sum->billable_transfers = $dates->sum('billable_transfers');
            $sum->failed_transfers = $dates->sum('failed_transfers');

            $sum->raw_hours = $dates->sum('raw_hours');
            $sum->eligible_hours = $dates->sum('eligible_hours');

            $sum->payroll_amount = round($sum->regular_amount + $sum->training_amount + $sum->qa_amount + $sum->overtime_amount + $sum->holiday_amount, 2);
            $sum->total_amount = round($sum->payroll_amount + $sum->bonus_amount, 2);
            $sum->total_hours = round($sum->regular_hours + $sum->training_hours + $sum->qa_hours + $sum->overtime_hours + $sum->holiday_hours, 2);

            $dates->each(function ($date) use (&$sum) {
                $dateStr = strtolower(Carbon::parse($date->file_date)->format('D'));

                $sum->{$dateStr} = $date->raw_hours;
                $sum->{"{$dateStr}_training"} = !empty($date->training);
                $sum->{"{$dateStr}_disabled"} = !empty($date->disabled);
                $sum->{"{$dateStr}_holiday"} = !empty($date->holiday_hours);
                $sum->{"{$dateStr}_editable_hours"} = round($date->raw_hours - $date->break_hours - $date->huddle_hours - $date->coaching_hours, 2);
                $sum->{"{$dateStr}_break_minutes"} = (string) round($date->break_hours * 60, 0);
                $sum->{"{$dateStr}_coaching_minutes"} = (string) round($date->coaching_hours * 60, 0);
                $sum->{"{$dateStr}_huddle_minutes"} = (string) round($date->huddle_hours * 60, 0);
                $sum->{"{$dateStr}_successful_transfers"} = !empty($date->transfers) ? $date->transfers : null;
                $sum->{"{$dateStr}_failed_transfers"} = !empty($date->failed_transfers) ? $date->failed_transfers : null;
                $sum->{"{$dateStr}_billable_transfers"} = !empty($date->billable_transfers) ? $date->billable_transfers : null;
                $sum->{"{$dateStr}_agent_average"} = $date->agent_average ?? 0;
                $sum->{"{$dateStr}_company_average"} = $date->company_average ?? 0;
                $sum->{"{$dateStr}_bonus_level"} = $date->bonus_level ?? 0;
                $sum->{"{$dateStr}_effective_bonus_rate"} = $date->effective_bonus_rate ?? 0;
                $sum->{"{$dateStr}_transfers"} = $date->transfers ?? 0;
                $sum->{"{$dateStr}_bonus_amount"} = $date->bonus_amount ?? 0;
            });

            return (array) $sum;
        })->filter(function ($agent) {
            return $agent['total_amount'] > 0;
        })
            ->filter(function ($row) use ($filters) {
                if (!empty($filters['product_ids'])) {
                    return in_array($row['internal_campaign_id'], $filters['product_ids']);
                }

                return true;
            })->sortBy([
                ['agent_type_id', 'asc'],
                ['agent_name', 'asc'],
                ['file_date', 'asc'],
            ])->values();
    }

    private static function debugRow($row): void
    {
        return;
        printf("%s %s RAW:%6s BT:%6s C:%6s H:%6s B:%6s".PHP_EOL,
            $row->agent_id,
            $row->file_date->format('Ymd'),
            $row->raw_hours,
            $row->billable_time_override,
            $row->coaching_hours,
            $row->huddle_hours,
            $row->break_hours,
        );
    }

    private static function calculateRawHours(&$row): void
    {
        $row->raw_hours = round($row->billable_time_override + $row->break_hours + $row->coaching_hours + $row->huddle_hours, 2);
    }

    private static function calculateBreakTime(&$row, $filters): void
    {
        //
        // Break Time
        // Relevant tickets: 7362, 7371, 7418
        //
        $row->break_hours = "0";

        // Break time logic was previously calculated in ProcessAgentPerformanceImport
        if ($row->file_date->lt(Carbon::parse('2023-05-15 00:00:00'))) {
            return;
        }

        // Only applies to billable hours.
        if (Company::DIALER_REPORT_TYPE_BILLABLE !== $filters['view']) {
            return;
        }

        // Only applies to hourly agents (Acquiro and external).
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            return;
        }

        // Does not apply to the first day 8-hour training override, but does apply to the subsequent 6 days of billable training.
        // Only applies to holiday hours if the agent worked that day.  If the agent is getting the 8-hour holiday override for not working, no break time is added.
        if ($row->disabled) {
            return;
        }

        // If more than 4 hours are worked, up to 30 minutes of break time is added (based on actual pause time).
        // Otherwise, if at least 15 minutes are worked, then up to 15 minutes of break time is added (based on actual pause time).
        if ($row->raw_hours > 4) {
            $row->break_hours = min(0.5, $row->pause_hours);
        } elseif ($row->raw_hours >= 0.25) {
            $row->break_hours = min(0.25, $row->pause_hours);
        }

        // Break time is included in the hours used to calculate billable overtime.
        $row->raw_hours += $row->break_hours;
        $row->eligible_hours += $row->break_hours;

        if ($row->training_hours > 0) {
            $row->training_hours += $row->break_hours;
            $row->training_amount = $row->training_hours * $row->training_rate;
        } else {
            $row->regular_hours += $row->break_hours;
            $row->regular_amount = $row->regular_hours * $row->regular_rate;
        }

    }

    private static function calculateBonus(&$row, $filters, $transfer_averages): void
    {
        //
        // Bonus Payments
        // Relevant tickets: 6356, 6718, 7315, 7371, 7891
        //
        $row->bonus_amount = 0;

        // Only applies to payable hours.
        if (Company::DIALER_REPORT_TYPE_PAYABLE !== $filters['view']) {
            return;
        }

        // Only applies to hourly and final transfer agents (Acquiro and external).
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            return;
        }

        // Abort if there's no bonus rate set
        if (empty($row->bonus_rate)) {
            return;
        }

        if (in_array($row->internal_campaign_id, [DialerProduct::FINAL_EXPENSE_INTEGRIANT, DialerProduct::ACA_INTEGRIANT])) {
            $row->effective_bonus_rate = $row->bonus_rate; // Default $0.75 USD
            $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
        } elseif ($row->internal_campaign_id === DialerProduct::MEDICARE_INTEGRIANT) {
            if ($row->file_date->gte(Carbon::parse("2024-01-15 00:00:00"))) {
                // 8280: New bonus target of 80% effective 2024-01-15
                // If you fall within 20% of the average, you will get your tiered bonus.
                // If you fall outside of 20% below the average, you will not get bonus.
                // So, the 20% decides whether you get a bonus, provided you are in your tier.

                $row->agent_average = 0;
                $row->company_average = 0;
                $transfer_average = $transfer_averages->where('file_date', $row->file_date->format('Y-m-d'))->first();
                if (!empty($transfer_average) && !empty($transfer_average->transfers)) {
                    $row->company_average = round($transfer_average->billable_transfers_bill_time / $transfer_average->transfers, 2);
                }

                // 8239: New bonus tiers effective 1/1/24.
                $low_threshold = 8;
                $medium_threshold = 10;
                $high_threshold = 13;

                if (!empty($row->transfers)) {
                    $row->agent_average = round($row->billable_transfers_bill_time / $row->transfers, 2);
                }

                if ($row->agent_average >= $row->company_average * 0.8) {
                    $row->bonus_level = ">=80% <{$low_threshold}";
                    if ($row->billable_transfers >= $high_threshold) {
                        $row->bonus_level = ">=80% >={$high_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate + 0.50; // $1.25 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    } elseif ($row->billable_transfers >= $medium_threshold) {
                        $row->bonus_level = ">=80% >={$medium_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate + 0.25; // $1.00 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    } elseif ($row->billable_transfers >= $low_threshold) {
                        $row->bonus_level = ">=80% >={$low_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate; // Default $0.75 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    }
                }
            } elseif ($row->file_date->gte(Carbon::parse("2023-05-15 00:00:00"))) {
                // 7315: New bonus structure effective 2023-05-15
                // If your average call length of billable transfers is 25%-49.9% below the average call length of the company, you will only receive $0.75 US per billable transfer.
                // If your average call length of billable transfers is 50% or more below the average call length of the company, you will not receive ANY bonus.
                // Otherwise, you get the normal payout scale
                $row->agent_average = 0;
                $row->company_average = 0;
                $transfer_average = $transfer_averages->where('file_date', $row->file_date->format('Y-m-d'))->first();
                if (!empty($transfer_average) && !empty($transfer_average->transfers)) {
                    $row->company_average = round($transfer_average->billable_transfers_bill_time / $transfer_average->transfers, 2);
                }

                // 7891: Adjust bonus thresholds for Medicare.
                // 8127: This ends when open enrollment closes on 12/7/23.
                // 8239: New bonus tiers effective 1/1/24.
                if ($row->file_date->gte(Carbon::parse("2024-01-01 00:00:00"))) {
                    $low_threshold = 8;
                    $medium_threshold = 10;
                    $high_threshold = 13;
                } elseif ($row->file_date->gte(Carbon::parse("2023-10-16 00:00:00")) && $row->file_date->lte(Carbon::parse("2023-12-10 23:59:59"))) {
                    $low_threshold = 9;
                    $medium_threshold = 12;
                    $high_threshold = 15;
                } else {
                    $low_threshold = 6;
                    $medium_threshold = 9;
                    $high_threshold = 12;
                }

                if (!empty($row->transfers)) {
                    $row->agent_average = round($row->billable_transfers_bill_time / $row->transfers, 2);
                }

                if ($row->agent_average > $row->company_average * 0.50 && $row->agent_average <= $row->company_average * 0.75) {
                    $row->bonus_level = '>50% <=75%';
                    if ($row->billable_transfers >= $low_threshold) {
                        $row->effective_bonus_rate = $row->bonus_rate; // Default $0.75 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    }
                } elseif ($row->agent_average > $row->company_average * 0.75) {
                    $row->bonus_level = ">75% <{$low_threshold}";
                    if ($row->billable_transfers >= $high_threshold) {
                        $row->bonus_level = ">75% >={$high_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate + 0.50; // $1.25 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    } elseif ($row->billable_transfers >= $medium_threshold) {
                        $row->bonus_level = ">75% >={$medium_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate + 0.25; // $1.00 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    } elseif ($row->billable_transfers >= $low_threshold) {
                        $row->bonus_level = ">75% >={$low_threshold}";
                        $row->effective_bonus_rate = $row->bonus_rate; // Default $0.75 USD
                        $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                    }
                }
            } elseif ($row->file_date->gte(Carbon::parse("2023-04-03 00:00:00"))) {
                // 7079: New bonus structure effective 2023-04-03
                if ($row->billable_transfers >= 12) {
                    $row->effective_bonus_rate = $row->bonus_rate + 0.50; // $1.25 USD
                    $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                } elseif ($row->billable_transfers >= 9) {
                    $row->effective_bonus_rate = $row->bonus_rate + 0.50; // $1.00 USD
                    $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                } elseif ($row->billable_transfers >= 6) {
                    $row->effective_bonus_rate = $row->bonus_rate; // Default $0.75 USD
                    $row->bonus_amount += $row->billable_transfers * $row->effective_bonus_rate;
                }
            } else {
                // 6718: Only paying on 7+ transfers now
                $row->bonus_amount += $row->billable_transfers >= 7 ? ($row->billable_transfers * $row->bonus_rate) : null;
            }

            // 6356: If at least 12 billable 7+ transfers, double all the bonus for that day.
            // 7679: This does not apply to the Final Expense campaign
            if ($row->file_date->lt(Carbon::parse("2023-01-01 00:00:00"))) {
                if ($row->billable_transfers >= 12) {
                    $row->bonus_amount *= 2;
                }
            }
        }
    }

    private static function calculateTraining(&$row, $filters): void
    {
        //
        // Training
        // Relevant tickets: 6356, 7519, 7615
        //
        $row->training = 0; // Used by the front-end to indicate whether training is in place for that day
        $row->training_rate = $row->training_hours = $row->training_amount = null;

        // Only applies to agents (Acquiro and external).
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            return;
        }

        // Only applies to hourly agents.
        if (DialerPaymentType::HOURLY !== $row->payment_type_id) {
            return;
        }

        // We cannot do any calculations without a start date (should not happen).
        if (empty($row->trainingStartDate)) {
            return;
        }

        // Only applies if they are in training :)
        if (empty($row->is_training)) {
            return;
        }

        // For all hourly agents (Acquiro and external):
        // - For payable, the first day of training is calculated as fixed 8 hours, multiplied by the agent’s regular payable rate.
        // - For billable, the first day of training is calculated as a fixed 8 hours, multiplied by the agent’s regular billable rate.  No break time is added.

        if ($row->is_initial_training) {
            $row->raw_hours = 8;
            $row->billable_time_override = 8; // In hours
            $row->coaching_hours = 0;
            $row->huddle_hours = 0;
            $row->training = 1;
            if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view']) {
                // 7615: New billable training logic effective 7/31/23
                // 7725: Back to the original rules
                if ($row->trainingStartDate->lt(Carbon::parse('2023-07-31')->setTime(0, 0)) || $row->trainingStartDate->gte(Carbon::parse('2023-08-28')->setTime(0, 0))) {
                    $row->training_rate = DialerAgent::TRAINING_BILLABLE_RATE;
                } else {
                    $row->training_rate = DialerAgent::TRAINING_BILLABLE_RATE_WEEK1;
                }
            } else {
                $row->training_rate = $row->regular_rate;
            }
            $row->disabled = true;
        }

        // For Acquiro hourly agents only:
        // - For payable, days 2 through 14 of training are calculated as the number of hours worked, multiplied by the system-wide payable training rate of $2.50 USD / hr.
        // - Before 7/31/23: For billable, the days 2 through 7 of training are calculated as the number of hours worked plus break time, multiplied by the system-wide billable training rate of $10 USD / hr.
        // - From 7/31/23: 7615: For billable, the initial calendar week of training is calculated as the number of hours worked plus Break Time plus Coaching Time, multiplied by the system-wide billable
        //      training rate of $8 USD / hr.  For the second calendar week of training, the billable amount is calculated as the number of hours worked plus Break Time plus Coaching Time,
        //      multiplied by the system-wide billable training rate of $9 USD / hr .

        if (Company::DIALER_REPORT_TYPE_PAYABLE === $filters['view'] &&
            in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS) &&
            (($row->file_date->gte(Carbon::parse('2022-10-31')->setTime(0, 0)) &&
                    $row->file_date->between($row->trainingStartDate, $row->trainingStartDate->addDays(13)))
                || ($row->file_date->lte(Carbon::parse('2022-10-30')->setTime(0, 0)) &&
                    $row->file_date->between($row->trainingStartDate, $row->trainingStartDate->addDays(6))))) {
            $row->training = 1;
            $row->training_rate = DialerAgent::TRAINING_PAYABLE_RATE;
        }

        if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view'] &&
            in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS)) {
            if ($row->trainingStartDate->lt(Carbon::parse('2023-07-31')->setTime(0, 0)) || $row->trainingStartDate->gte(Carbon::parse('2023-08-28')->setTime(0, 0))) {
                // Integriant only gets billed the first 7 days of training
                if ($row->file_date->between($row->trainingStartDate, $row->trainingStartDate->addDays(6))) {
                    $row->training = 1;
                    $row->training_rate = DialerAgent::TRAINING_BILLABLE_RATE;
                }
            } else {
                // 7615: Integriant gets billed the first two calendar weeks of training, with different rates for week 1 and week 2
                if ($row->file_date->between($row->trainingStartDate->startOfWeek(), $row->trainingStartDate->endOfWeek())) {
                    $row->training = 1;
                    $row->training_rate = DialerAgent::TRAINING_BILLABLE_RATE_WEEK1;
                } elseif ($row->file_date->between($row->trainingStartDate->startOfWeek()->addWeek(1), $row->trainingStartDate->startOfWeek()->addWeek(1)->endOfWeek())) {
                    $row->training = 1;
                    $row->training_rate = DialerAgent::TRAINING_BILLABLE_RATE_WEEK2;
                }
            }
        }

        if ($row->training) {
            $row->training_hours = $row->raw_hours;
            $row->training_amount = round($row->training_hours * $row->training_rate, 2);
            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
            $row->eligible_hours = $row->training_hours;
        }
    }

    private static function calculateSalary(&$row, $filters): void
    {
        //
        // QA/Employees on Salary
        // Relevant tickets:
        //
        $row->qa_rate = $row->qa_hours = $row->qa_amount = null;

        // Only applies to employees.
        if (DialerAgentType::VISIBLE_EMPLOYEE !== $row->agent_type_id) {
            return;
        }

        // Only applies to salary employees.
        if (DialerPaymentType::SALARY !== $row->payment_type_id) {
            return;
        }

        // Company::DIALER_REPORT_TYPE_PAYABLE === $filters['view']
        // TODO: Hide the rates in the M, Tu, W, Th, F list

        $row->qa_rate = $row->{$filters['view'].'_rate'};
        $row->qa_hours = $row->raw_hours;
        $row->qa_amount = round($row->qa_hours * $row->qa_rate, 2);
    }

    private static function calculateHourly(&$row, $filters): void
    {
        //
        // Hourly and Final Transfer agents
        // Relevant tickets:
        //
        $row->regular_rate = $row->regular_hours = $row->regular_amount = null;

        // Only applies to hourly and final transfer agents (Acquiro and external).
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            return;
        }

        $row->regular_rate = $row->{$filters['view'].'_rate'};
        $row->regular_hours = $row->raw_hours;
        $row->regular_amount = round($row->regular_hours * $row->regular_rate, 2);

        $row->eligible_hours = $row->raw_hours;
    }

    private static function calculateOvertime($agent, $filters): void
    {
        //
        // Overtime
        // Relevant tickets:
        //

        // Only applies to agents (Acquiro and external).
        if (DialerAgentType::AGENT !== $agent[0]->agent_type_id) {
            return;
        }

        // Only applies to hourly and final transfer payment types.
        if (!in_array($agent[0]->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            return;
        }

        $eligible_hours = $agent->sum('eligible_hours');
        $eligible_medicare_hours = $agent->where('internal_campaign_id', DialerProduct::MEDICARE_INTEGRIANT)->sum('eligible_hours');
        $remaining_hours = 0;
        $first_row = true;

        $agent->groupBy('effective_date_id')->sortByDesc(function ($rows) {
            return $rows->sum('eligible_hours');
        })->transform(function ($rows) use (&$eligible_hours, &$remaining_hours, $eligible_medicare_hours, &$first_row, $filters) {
            $rows->transform(function ($row) use (&$eligible_hours, &$remaining_hours, $eligible_medicare_hours, &$first_row, $filters) {

                // 7891: Put all over the overtime under the first row (the campaign with the most hours for the week)
                if ($first_row) {
                    // 7891: The payable overtime threshold lowers to 35 hours on 10/16/2023
                    // 8127: This ends when open enrollment closes on 12/7/23.
                    $payableHoursThreshold = DialerAgent::OVERTIME_PAYABLE_HOURS;
                    if ($row->file_date->gte(Carbon::parse("2023-10-16 00:00:00")) && $row->file_date->lte(Carbon::parse("2023-12-10 23:59:59"))) {
                        $payableHoursThreshold = DialerAgent::OVERTIME_PAYABLE_HOURS_ACA;
                    }

                    if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view'] && $eligible_hours > DialerAgent::OVERTIME_BILLABLE_HOURS) {
                        $row->overtime_hours = round($eligible_hours - DialerAgent::OVERTIME_BILLABLE_HOURS, 2);
                        $remaining_hours = $row->overtime_hours;
                        $row->overtime_rate = DialerAgent::OVERTIME_BILLABLE_RATE;
                        $row->overtime_amount = round($row->overtime_hours * $row->overtime_rate, 2);
                    }

                    if ($eligible_medicare_hours && Company::DIALER_REPORT_TYPE_PAYABLE === $filters['view'] && $eligible_hours > $payableHoursThreshold) {
                        $row->overtime_hours = round($eligible_hours - $payableHoursThreshold, 2);
                        $remaining_hours = $row->overtime_hours;
                        if ($row->training_hours >= $remaining_hours) {
                            $row->overtime_rate = $row->training_rate * 1.5;
                        } else {
                            $row->overtime_rate = $row->regular_rate * 1.5;
                        }
                        $row->overtime_amount = round($row->overtime_hours * $row->overtime_rate, 2);
                    }
                    $first_row = false;
                }

                // Nibble away at the regular and training hours until all the overtime is reallocated
                if ($remaining_hours > 0 && $row->regular_hours > 0) {
                    $subtract = min($row->regular_hours, $remaining_hours);
                    $remaining_hours -= $subtract;
                    $row->regular_hours -= $subtract;
                    $row->regular_amount = round($row->regular_hours * $row->regular_rate, 2);
                }

                if ($remaining_hours > 0 && $row->training_hours > 0) {
                    $subtract = min($row->training_hours, $remaining_hours);
                    $remaining_hours -= $subtract;
                    $row->training_hours -= $subtract;
                    $row->training_amount = round($row->training_hours * $row->training_rate, 2);
                }
            });
        });
    }

    private static function calculateHuddle($row, $filters): void
    {
        //
        // Huddle Time
        // Relevant tickets: 7606
        //

        // Only applies starting 8/7/23.
        if ($row->file_date->lt(Carbon::parse('2023-08-07 00:00:00'))) {
            $row->huddle_hours = 0;

            return;
        }

        // Only applies to hourly and final transfer Acquiro agents.
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            $row->huddle_hours = 0;

            return;
        }

        // Only applies to agents.
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            $row->huddle_hours = 0;

            return;
        }

        // Only applies to Acquiro agents.
        if (!in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS)) {
            $row->huddle_hours = 0;

            return;
        }

        // 7606: Huddle time is not billable, so override it to zero.
        if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view']) {
            $row->huddle_hours = 0;

            return;
        }

    }

    private static function calculateCoaching($row, $filters): void
    {
        //
        // Coaching Time
        // Relevant tickets: 7474, 7683, 7689
        //

        // Only applies starting 8/7/23.
        if ($row->file_date->lt(Carbon::parse('2023-08-07 00:00:00'))) {
            $row->coaching_hours = 0;

            return;
        }

        // Only applies to hourly and final transfer Acquiro agents.
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            $row->coaching_hours = 0;

            return;
        }

        // Only applies to agents.
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            $row->coaching_hours = 0;

            return;
        }

        // Before 8/14/23, only applies to Acquiro agents.  Starting 8/14/23, applies to both Acquiro and external agents.
        if ($row->file_date->lt(Carbon::parse('2023-08-14 00:00:00')) && !in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS)) {
            $row->coaching_hours = 0;

            return;
        }
    }

    private static function calculateOverUnder(
        &$row,
        $rows,
        $filters
    ): void {
        //
        // Over/Under Payable Rate
        // Relevant tickets: 6718, 6752, 6871, 7362, 7514
        //

        // Only applies to payable hours.
        if (Company::DIALER_REPORT_TYPE_PAYABLE !== $filters['view']) {
            return;
        }

        // Only applies to Acquiro.
        if (!in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS)) {
            return;
        }

        // Only applies to Acquiro agents.
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            return;
        }

        // Only applies to hourly and final transfer.
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            return;
        }

        // 6752: If they are in training, keep the training rate no matter if they are under 40 hours
        if (!empty($row->training_hours)) {
            return;
        }

        // 6718: This logic is effective 1/1/23.
        if (Carbon::parse($filters['start_date'])->lt(Carbon::parse("2023-01-01 00:00:00"))) {
            return;
        }

        // 7514: Disable over/under logic effective 7/10/23.
        if (Carbon::parse($filters['start_date'])->gte(Carbon::parse("2023-07-10 00:00:00"))) {
            return;
        }

        // 7362: Lower under/over threshold
        if ($row->file_date->lt(Carbon::parse("2023-05-15 00:00:00"))) {
            $hours_threshold = 40;
        } else {
            $hours_threshold = 37.5;
        }

        // 6718: If they work less than 40 hours for the week, pay at a lower rate.  Effective 1/1/23.
        // 6871: Confirming the new rates
        $raw_hours = $rows->where('agent_id', $row->agent_id)->sum('raw_hours');
        if ($raw_hours > 0 && $raw_hours < $hours_threshold && !empty($row->regular_rate)) {
            // Agent hire date is before 1/1/2023.
            if ($row->effectiveHireDate->gte(Carbon::parse("2023-01-01 00:00:00"))) {
                $row->regular_rate -= 0.50;
            } else {
                $row->regular_rate -= 0.75;
            }
            $row->regular_amount = round($row->regular_hours * $row->regular_rate, 2);
        }
    }

    private static function calculateMissingBelizeHolidayHours(
        &$rows,
        $date,
        $holiday,
        $filters
    ): void {
        //
        // Belize Holidays (if not worked)
        // Relevant tickets: 6718, 6752, 6871, 7362
        //

        // Look up the previous work day (excluding weekends)
        $holidayPrevWorkday = Carbon::parse($holiday->holiday)->setTime(0, 0)->subDay();
        while ($holidayPrevWorkday->isWeekend()) {
            $holidayPrevWorkday->subDay();
        }

        // Pull payroll data from the previous work day
        $previousDayAgents = self::getWeeklyValues(array_merge($filters, [
            'start_date' => $holidayPrevWorkday,
            'end_date' => $holidayPrevWorkday,
        ]))
            ->where('agent_type_id', DialerAgentType::AGENT)
            ->where('payment_type_id', DialerPaymentType::HOURLY)
            ->whereIn('company_id', DialerAgent::PAYROLL_COMPANY_IDS)
            ->where('eligible_hours', '>', 0)
            ->pluck('agent_id')
            ->unique();

        // Look up the next work day (excluding weekends)
        $holidayNextWorkday = Carbon::parse($holiday->holiday)->setTime(0, 0)->addDay();
        while ($holidayNextWorkday->isWeekend()) {
            $holidayNextWorkday->addDay();
        }

        // Pull payroll data from the next work day
        $nextDayAgents = self::getWeeklyValues(array_merge($filters, [
            'start_date' => $holidayNextWorkday,
            'end_date' => $holidayNextWorkday,
        ]))
            ->where('agent_type_id', DialerAgentType::AGENT)
            ->where('payment_type_id', DialerPaymentType::HOURLY)
            ->whereIn('company_id', DialerAgent::PAYROLL_COMPANY_IDS)
            ->where('eligible_hours', '>', 0)
            ->pluck('agent_id')
            ->unique();

        // Exclude any agents who worked the holiday
        $excludeAgents = $rows->filter(function ($row) use ($date) {
            return $row->file_date->eq($date);
        })
            ->where('holiday_hours', '>', 0)
            ->pluck('agent_id');
        $missingAgents = $previousDayAgents->intersect($nextDayAgents)->diff($excludeAgents);

        $missingAgents->each(function ($agent_id) use ($date, $filters, $holiday, &$rows) {
            $row = (object) [];

            $agent = DialerAgent::find($agent_id);
            $effectiveDateRow = $agent->getEffectiveValuesForDate($date);

            $row->agent_id = $agent->id;
            $row->agent_name = $agent->agent_name;
            $row->agent = $agent;
            $row->agent_name_role = $row->agent_name.(!empty($row->agent?->accessRole?->abbreviation) ? " (".$row->agent?->accessRole?->abbreviation.")" : '');
            $row->effective_date_id = $effectiveDateRow->id;
            $row->internal_campaign_name = $effectiveDateRow->product->name;
            $row->internal_campaign_id = $effectiveDateRow->product_id;
            $row->file_date = CarbonImmutable::parse($date);
            $row->agent_type_id = $effectiveDateRow->agent_type_id;
            $row->company_id = $agent->company_id;
            $row->start_date = $agent->start_date;
            $row->payment_type_id = $effectiveDateRow->payment_type_id;
            $row->product_id = $effectiveDateRow->product_id;

            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
            $row->training_rate = $row->training_hours = $row->training_amount = null;
            $row->coaching_hours = null;
            $row->huddle_hours = null;
            $row->qa_rate = $row->qa_hours = $row->qa_amount = null;
            $row->break_hours = null;
            $row->eligible_hours = null; // Hours eligible for overtime
            $row->bonus_amount = null; // There can't be a bonus if no real hours were logged.

            $row->disabled = true;
            $row->holiday_hours = $row->raw_hours = 8;
            $row->holiday_rate = $effectiveDateRow->{$filters['view'].'_rate'};
            $row->holiday_amount = round($row->holiday_hours * $row->holiday_rate, 2);

            // Remove any existing rows for this same agent and date to prevent conflicts
            $rows = $rows->reject(function ($row_iterator) use ($row) {
                return $row_iterator->agent_id === $row->agent_id && $row_iterator->file_date->eq($row->file_date);
            });

            $rows->push($row);
        });
    }

    private
    static function calculateMissingUSHolidayHours(
        &$rows,
        $date,
        $holiday,
        $filters
    ): void {
        //
        // US Holidays (if not worked)
        // Relevant tickets: 6718, 6752, 6871, 7362, 7740
        //

        // 7740: Disabled starting with Labor Day 2023
        if (CarbonImmutable::parse($date)->gt(Carbon::parse("2023-09-01 00:00:00"))) {
            return;
        }

        // Only applies to payable hours.
        if (Company::DIALER_REPORT_TYPE_PAYABLE !== $filters['view']) {
            return;
        }

        // Look up the previous work day (excluding weekends)
        $holidayPrevWorkday = Carbon::parse($holiday->holiday)->setTime(0, 0)->subDay();
        while ($holidayPrevWorkday->isWeekend()) {
            $holidayPrevWorkday->subDay();
        }

        // Pull payroll data from the previous work day
        $previousDayAgents = self::getWeeklyValues(array_merge($filters, [
            'start_date' => $holidayPrevWorkday,
            'end_date' => $holidayPrevWorkday,
        ]))
            ->where('agent_type_id', DialerAgentType::AGENT)
            ->where('payment_type_id', DialerPaymentType::HOURLY)
            ->whereIn('company_id', DialerAgent::PAYROLL_COMPANY_IDS)
            ->where('eligible_hours', '>', 0)
            ->pluck('agent_id')
            ->unique();

        // Look up the next work day (excluding weekends)
        $holidayNextWorkday = Carbon::parse($holiday->holiday)->setTime(0, 0)->addDay();
        while ($holidayNextWorkday->isWeekend()) {
            $holidayNextWorkday->addDay();
        }

        // Pull payroll data from the next work day
        $nextDayAgents = self::getWeeklyValues(array_merge($filters, [
            'start_date' => $holidayNextWorkday,
            'end_date' => $holidayNextWorkday,
        ]))
            ->where('agent_type_id', DialerAgentType::AGENT)
            ->where('payment_type_id', DialerPaymentType::HOURLY)
            ->whereIn('company_id', DialerAgent::PAYROLL_COMPANY_IDS)
            ->where('eligible_hours', '>', 0)
            ->pluck('agent_id')
            ->unique();

        // Exclude any agents who worked the holiday
        $excludeAgents = $rows->where('file_date', $date->format('Y-m-d'))->where('holiday_hours', '>', 0)->pluck('agent_id');
        $missingAgents = $previousDayAgents->intersect($nextDayAgents)->diff($excludeAgents);

        $missingAgents->each(function ($agent_id) use ($date, $filters, $holiday, &$rows) {
            $row = (object) [];

            $agent = DialerAgent::find($agent_id);
            $effectiveDateRow = $agent->getEffectiveValuesForDate($date);

            $row->agent_id = $agent->id;
            $row->agent_name = $agent->agent_name;
            $row->agent = $agent;
            $row->agent_name_role = $row->agent_name.(!empty($row->agent?->accessRole?->abbreviation) ? " (".$row->agent?->accessRole?->abbreviation.")" : '');
            $row->effective_date_id = $effectiveDateRow->id;
            $row->internal_campaign_name = $effectiveDateRow->product->name;
            $row->internal_campaign_id = $effectiveDateRow->product_id;
            $row->file_date = CarbonImmutable::parse($date);
            $row->agent_type_id = $effectiveDateRow->agent_type_id;
            $row->company_id = $agent->company_id;
            $row->start_date = $agent->start_date;
            $row->payment_type_id = $effectiveDateRow->payment_type_id;
            $row->product_id = $effectiveDateRow->product_id;

            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
            $row->training_rate = $row->training_hours = $row->training_amount = null;
            $row->coaching_hours = null;
            $row->huddle_hours = null;
            $row->qa_rate = $row->qa_hours = $row->qa_amount = null;
            $row->break_hours = null;
            $row->eligible_hours = null; // Hours eligible for overtime
            $row->bonus_amount = null; // There can't be a bonus if no real hours were logged.

            $row->disabled = true;
            $row->holiday_hours = $row->raw_hours = 8;
            $row->holiday_rate = DialerAgent::TRAINING_PAYABLE_RATE;
            $row->holiday_amount = round($row->holiday_hours * $row->holiday_rate, 2);

            // Remove any existing rows for this same agent and date to prevent conflicts
            $rows = $rows->reject(function ($row_iterator) use ($row) {
                return $row_iterator->agent_id === $row->agent_id && $row_iterator->file_date->eq($row->file_date);
            });

            $rows->push($row);
        });
    }

    private
    static function calculateWorkedUSHolidayHours(
        &$row,
        $date,
        $holiday,
        $filters
    ): void {
        //
        // US Holidays (if worked or if hours manually entered)
        // Relevant tickets: 6718, 6752, 6871, 7362, 7740
        //

        // 7740: Disabled before Labor Day 2023
        if (CarbonImmutable::parse($date)->lt(Carbon::parse("2023-09-01 00:00:00"))) {
            return;
        }

        // Ensure the dates match
        if (Carbon::parse($row->file_date)->ne($date)) {
            return;
        }

        // Only applies to payable hours.
        if (Company::DIALER_REPORT_TYPE_PAYABLE !== $filters['view']) {
            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
            $row->eligible_hours = -$row->regular_hours;

            return;
        }

        if ($row->regular_hours > 0) {
            $row->holiday_hours += $row->regular_hours;
            $row->holiday_rate = DialerAgent::TRAINING_PAYABLE_RATE;
            $row->holiday_amount = round($row->holiday_hours * $row->holiday_rate, 2);

            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
        }
    }

    private
    static function calculateWorkedBelizeHolidayHours(
        &$row,
        $date,
        $holiday,
        $filters
    ): void {
        //
        // Belize Holidays (if worked)
        // Relevant tickets: 6718, 6752, 6871, 7362
        //
        $row->holiday_rate = $row->holiday_hours = $row->holiday_amount = null;

        // Only applies to Acquiro agents.
        if (!in_array($row->company_id, DialerAgent::PAYROLL_COMPANY_IDS)) {
            return;
        }

        // Only applies to agents (Acquiro and external).
        if (DialerAgentType::AGENT !== $row->agent_type_id) {
            return;
        }

        // Only applies to hourly and final transfer agents (Aquiro and external).
        if (!in_array($row->payment_type_id, [DialerPaymentType::HOURLY, DialerPaymentType::FINAL_TRANSFER])) {
            return;
        }

        // Ensure the dates match
        if (Carbon::parse($row->file_date)->ne($date)) {
            return;
        }

        if ($row->regular_hours > 0) {
            $row->holiday_hours += $row->regular_hours;

            // Integriant has special rates for the holiday: if a 1.5 multiplier, hourly rate is $16 ... or 2.0 multiplier, hourly rate is $19.
            if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view']) {
                switch ($holiday->multiplier) {
                    case 2.0:
                        $row->holiday_amount += round($row->regular_hours * DialerAgent::INTEGRIANT_HOLIDAY_RATE_20_MULTIPLIER, 2);
                        break;

                    case 1.5:
                        $row->holiday_amount += round($row->regular_hours * DialerAgent::INTEGRIANT_HOLIDAY_RATE_15_MULTIPLIER, 2);
                        break;

                    default:
                        // Make sure missing cases stand out
                        $row->holiday_amount += round($row->regular_hours * 9999.99, 2);
                        break;
                }
            } else {
                $row->holiday_amount += round($row->regular_hours * round($row->regular_rate * $holiday->multiplier, 2), 2);
            }

            $row->regular_rate = $row->regular_hours = $row->regular_amount = null;
        }
        if ($row->training_hours > 0) {
            $row->holiday_hours += $row->training_hours;
            // Integriant has special rates for the holiday: if a 1.5 multiplier, hourly rate is $16 ... or 2.0 multiplier, hourly rate is $19.
            if (Company::DIALER_REPORT_TYPE_BILLABLE === $filters['view']) {
                switch ($holiday->multiplier) {
                    case 2.0:
                        $row->holiday_amount += round($row->training_hours * DialerAgent::INTEGRIANT_HOLIDAY_RATE_20_MULTIPLIER, 2);
                        break;

                    case 1.5:
                        $row->holiday_amount += round($row->training_hours * DialerAgent::INTEGRIANT_HOLIDAY_RATE_15_MULTIPLIER, 2);
                        break;

                    default:
                        // Make sure missing cases stand out
                        $row->holiday_amount += round($row->training_hours * 9999.99, 2);
                        break;
                }
            } else {
                $row->holiday_amount += round(($row->training_hours * round($row->training_rate * $holiday->multiplier, 2)), 2);
            }
            $row->training_rate = $row->training_hours = $row->training_amount = null;
        }

        if ($row->holiday_hours > 0) {
            $row->holiday_rate = round($row->holiday_amount / $row->holiday_hours, 2);
        }
    }
}
