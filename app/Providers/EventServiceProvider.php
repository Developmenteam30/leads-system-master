<?php

namespace App\Providers;

use App\Models\DialerAccessArea;
use App\Models\DialerAccessRole;
use App\Models\DialerAccessRolesArea;
use App\Models\DialerAccessRolesNotification;
use App\Models\DialerAgent;
use App\Models\DialerAgentCompany;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentEvaluation;
use App\Models\DialerAgentPerformance;
use App\Models\DialerAgentPip;
use App\Models\DialerAgentTermination;
use App\Models\DialerAgentTerminationReason;
use App\Models\DialerAgentWriteup;
use App\Models\DialerAgentWriteupLevel;
use App\Models\DialerAgentWriteupReason;
use App\Models\DialerCampaignDefault;
use App\Models\DialerDocument;
use App\Models\DialerDocumentType;
use App\Models\DialerExternalCampaign;
use App\Models\DialerHoliday;
use App\Models\DialerHolidayHolidayList;
use App\Models\DialerHolidayList;
use App\Models\DialerLeaveRequest;
use App\Models\DialerLeaveRequestType;
use App\Models\DialerNotificationType;
use App\Models\DialerPettyCashEntry;
use App\Models\DialerPettyCashLocation;
use App\Models\DialerPettyCashNote;
use App\Models\DialerPettyCashReason;
use App\Models\DialerPettyCashVendor;
use App\Models\DialerPipReason;
use App\Models\DialerProduct;
use App\Models\DialerTeam;
use App\Models\DialerTeamLead;
use App\Observers\DialerAgentEffectiveDateObserver;
use App\Observers\DialerAgentObserver;
use App\Observers\DialerAgentPerformanceObserver;
use App\Observers\GenericObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        DialerAccessArea::observe(GenericObserver::class);
        DialerAccessRole::observe(GenericObserver::class);
        DialerAccessRolesArea::observe(GenericObserver::class);
        DialerAccessRolesNotification::observe(GenericObserver::class);
        DialerAgent::observe(DialerAgentObserver::class);
        DialerAgentCompany::observe(GenericObserver::class);
        DialerAgentEffectiveDate::observe(DialerAgentEffectiveDateObserver::class);
        DialerAgentEvaluation::observe(GenericObserver::class);
        DialerAgentPerformance::observe(DialerAgentPerformanceObserver::class);
        DialerAgentPip::observe(GenericObserver::class);
        DialerAgentTermination::observe(GenericObserver::class);
        DialerAgentTerminationReason::observe(GenericObserver::class);
        DialerAgentWriteup::observe(GenericObserver::class);
        DialerAgentWriteupLevel::observe(GenericObserver::class);
        DialerAgentWriteupReason::observe(GenericObserver::class);
        DialerCampaignDefault::observe(GenericObserver::class);
        DialerDocument::observe(GenericObserver::class);
        DialerDocumentType::observe(GenericObserver::class);
        DialerExternalCampaign::observe(GenericObserver::class);
        DialerHoliday::observe(GenericObserver::class);
        DialerHolidayHolidayList::observe(GenericObserver::class);
        DialerHolidayList::observe(GenericObserver::class);
        DialerLeaveRequest::observe(GenericObserver::class);
        DialerLeaveRequestType::observe(GenericObserver::class);
        DialerNotificationType::observe(GenericObserver::class);
        DialerPettyCashEntry::observe(GenericObserver::class);
        DialerPettyCashLocation::observe(GenericObserver::class);
        DialerPettyCashNote::observe(GenericObserver::class);
        DialerPettyCashReason::observe(GenericObserver::class);
        DialerPettyCashVendor::observe(GenericObserver::class);
        DialerPipReason::observe(GenericObserver::class);
        DialerProduct::observe(GenericObserver::class);
        DialerTeam::observe(GenericObserver::class);
        DialerTeamLead::observe(GenericObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
