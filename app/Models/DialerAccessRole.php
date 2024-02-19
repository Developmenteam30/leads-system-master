<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerAccessRole extends Model
{
    const ACCESS_ROLE_ADMIN = 1;
    const ACCESS_ROLE_DEVELOPER = 11;
    const ACCESS_ROLE_AGENT = 8;

    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function accessAreas()
    {
        return $this->belongsToMany(DialerAccessArea::class, 'dialer_access_roles_areas', 'role_id', 'area_id')->using(DialerAccessRolesArea::class);
    }

    public function notificationTypes()
    {
        return $this->belongsToMany(DialerNotificationType::class, 'dialer_access_roles_notifications', 'role_id', 'notification_type_id')->using(DialerAccessRolesNotification::class);
    }

    public function agents()
    {
        return $this->hasMany(DialerAgent::class, 'role_id', 'id');
    }

    public function getAccessAreasListAttribute()
    {
        return $this->accessAreas->pluck('id')->toArray();
    }

    public function getNotificationTypesListAttribute()
    {
        return $this->notificationTypes->pluck('id')->toArray();
    }
}
