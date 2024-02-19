<?php

namespace App\Console\Commands\Admin;

use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentType;
use App\Models\DialerProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateBillableRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:update-billable-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the billable rate for active agents';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if ($this->confirm('Are you sure you wish to reset billable rates?  Please check the code first.')) {
            $dates = DialerAgentEffectiveDate::query()
                ->where('product_id', DialerProduct::MEDICARE_INTEGRIANT)
                ->whereIn('agent_type_id', [DialerAgentType::AGENT, DialerAgentType::VISIBLE_EMPLOYEE])
                ->whereNull('end_date')
                ->where('billable_rate', 11)
                ->get();

            foreach ($dates as $date) {
                print "{$date->id} {$date->agent_id}\r\n";

                DB::beginTransaction();

                $date->end_date = '2023-10-15';
                $date->termination_reason_id = 19;
                $date->save();

                $new = $date->replicate();
                $new->is_training = 0;
                $new->billable_rate = 13.00;
                $new->start_date = '2023-10-16';
                $new->end_date = null;
                $new->save();

                DB::commit();
            }
        }

        return true;
    }
}
