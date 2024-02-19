<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\App;

class DialerNotificationType extends Model
{
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function accessRoles()
    {
        return $this->belongsToMany(DialerAccessRole::class, 'dialer_access_roles_notifications', 'notification_type_id', 'role_id')->using(DialerAccessRolesNotification::class);
    }

    public static function getEmailsForNotificationType(string $slug, $onlyInProduction = false): \Illuminate\Support\Collection
    {
        // Default to developer notifications if not in production
        if ($onlyInProduction && !App::environment('production')) {
            return collect([new Address(config('settings.developer_email'))]);
        }

        $agents = DialerAgent::query()
            ->join('dialer_access_roles', 'dialer_access_roles.id', 'dialer_agents.access_role_id')
            ->join('dialer_access_roles_notifications', 'dialer_access_roles.id', 'dialer_access_roles_notifications.role_id')
            ->join('dialer_notification_types', 'dialer_notification_types.id', 'dialer_access_roles_notifications.notification_type_id')
            ->where('dialer_notification_types.slug', $slug)
            ->whereNotNull('dialer_agents.access_role_id')
            ->whereNotNull('dialer_agents.email')
            ->isActiveForDate(now(config('settings.timezone.local'))->format('Y-m-d'))
            ->distinct('dialer_agents.email')
            ->select([
                'dialer_agents.agent_name',
                'dialer_agents.email',
            ])
            ->get();

        return $agents->map(function ($agent) {
            return new Address($agent->email, $agent->agent_name);
        });
    }
}
