<?php

namespace App\Http\Controllers\Api\Reports;

use App\Jobs\GenerateLicenseSwapAssignmentsEmail;
use App\Models\AuditLog;
use App\Models\DialerReportLicenseSwap;
use App\Responses\ErrorResponse;
use App\Services\RefreshLicenseSwapReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class LicenseSwapController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        AuditLog::createFromRequest($request, 'REPORT:LICENSE-SWAP');

        if ($request->boolean('refresh')) {
            RefreshLicenseSwapReportService::handle(CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local'))));
        }

        return DialerReportLicenseSwap::first();
    }

    /**
     * Email reassigned licenses
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function email(Request $request)
    {
        $licenses = array_filter($request->input('licenses'));
        if (empty($licenses)) {
            return ErrorResponse::json('No reassignments were made', 400);
        }

        GenerateLicenseSwapAssignmentsEmail::dispatch(
            licenses: $licenses,
        );

        return response([]);
    }
}

