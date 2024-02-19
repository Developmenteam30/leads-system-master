<?php

namespace App\Services;

use App\External\Convoso\APIRequest;
use App\External\Convoso\WebRequest;
use App\Models\DialerExternalCampaign;
use App\Models\DialerReportLicenseSwap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshLicenseSwapReportService
{
    /**
     * @throws \Throwable
     */
    public static function handle(CarbonImmutable $date)
    {
        try {
            DB::beginTransaction();

            $cache = DialerReportLicenseSwap::firstOrCreate();

            $convosoWeb = new WebRequest();
            $licenses = $convosoWeb->getLicenses();

            $convosoAPI = new APIRequest();
            $active_agents = collect($convosoAPI->getAgentPerformance([
                'date_start' => $date->startOfDay()->toDateTimeLocalString(),
                'date_end' => $date->endofDay()->toDateTimeLocalString(),
                'campaign_ids' => DialerExternalCampaign::pluck('id')->implode(','),
            ]));

            // Map licenses to users
            $licenses->assigned_licenses->map(function ($license) use ($licenses) {
                $user = $licenses->users->where('id', $license->user_id)->first();
                if ($user) {
                    $license->name = $user->first_name.' '.$user->last_name;
                }

                return $license;
            });

            // Remove non-integer agents
            $licenses->assigned_licenses = $licenses->assigned_licenses->filter(function ($agent) {
                return preg_match('/^[0-9]/', $agent->name);
            });

            // Remove non-integer agents
            $active_agents = $active_agents->filter(function ($agent) {
                return preg_match('/^[0-9]/', $agent->name);
            });

            $available_licenses = $licenses->assigned_licenses->filter(function ($agent) use ($active_agents) {
                return $active_agents->doesntContain('user_id', $agent->user_id);
            });

            $cache->data = [
                'summary' => $licenses->summary,
                'available_licenses' => $available_licenses->sortBy('name')->values(),
                'assigned_licenses' => $licenses->assigned_licenses->sortBy('name')->values(),
                'active_agents' => $active_agents->sortBy('name')->values(),
            ];

            $cache->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            Log::error(self::class." Exception: {$e->getMessage()}");

            throw $e;
        }
    }
}
