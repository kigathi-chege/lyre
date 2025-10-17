<?php

namespace Lyre\Strings\Model\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Handles activity logging for models.
 * 
 * This concern provides methods for configuring activity logging
 * with proper options and settings.
 * 
 * @package Lyre\Strings\Model\Concerns
 */
trait HandlesActivityLogging
{
    use LogsActivity;

    /**
     * Get activity log options.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        if (config('lyre.activity-log')) {
            return LogOptions::defaults()
                ->logAll()
                ->logOnlyDirty();
        }

        // TODO: Haven't figured out how to turn off activity log if not activated in the config
        return LogOptions::defaults()
            ->logOnly([]);
    }
}
