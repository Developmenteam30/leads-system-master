<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class GenericObserver
{
    /**
     * Handle the Model "creating" event.
     */
    public function creating(Model $item): void
    {
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $item): void
    {
        AuditLog::createFromRequest(app('Illuminate\Http\Request'), get_class($item).':CREATE', [
            'oldValues' => $item->getOriginal(),
            'newValues' => $item->setHidden([])->toArray(),
        ]);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $item): void
    {
        AuditLog::createFromRequest(app('Illuminate\Http\Request'), get_class($item).':UPDATE', [
            'oldValues' => $item->getOriginal(),
            'newValues' => $item->setHidden([])->toArray(),
        ]);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $item): void
    {
        AuditLog::createFromRequest(app('Illuminate\Http\Request'), get_class($item).':DELETE', [
            'oldValues' => $item->getOriginal(),
            'newValues' => [],
        ]);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $item): void
    {
        AuditLog::createFromRequest(app('Illuminate\Http\Request'), get_class($item).':RESTORE', [
            'oldValues' => $item->getOriginal(),
            'newValues' => $item->setHidden([])->toArray(),
        ]);
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $item): void
    {
        AuditLog::createFromRequest(app('Illuminate\Http\Request'), get_class($item).':FORCE-DELETE', [
            'oldValues' => $item->getOriginal(),
            'newValues' => [],
        ]);
    }
}
