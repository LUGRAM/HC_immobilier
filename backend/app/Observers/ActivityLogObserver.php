<?php

// ============================================
// OBSERVER: app/Observers/ActivityLogObserver.php
// ============================================
namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        ActivityLog::logActivity(
            action: 'created',
            model: $model,
            newValues: $model->getAttributes()
        );
    }

    public function updated(Model $model): void
    {
        ActivityLog::logActivity(
            action: 'updated',
            model: $model,
            oldValues: $model->getOriginal(),
            newValues: $model->getChanges()
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLog::logActivity(
            action: 'deleted',
            model: $model,
            oldValues: $model->getAttributes()
        );
    }
}