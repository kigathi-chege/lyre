<?php

namespace Lyre\Strings\Model;

use Lyre\Strings\Model\Concerns\HandlesActivityLogging;
use Lyre\Strings\Model\Concerns\HandlesConfiguration;
use Lyre\Strings\Model\Concerns\HandlesCustomColumns;
use Lyre\Strings\Model\Concerns\HandlesRelationships;
use Lyre\Strings\Model\Concerns\HandlesUtilities;
use Lyre\Strings\Traits\CanIncludeColumns;
use Lyre\Strings\Traits\BelongsToTenant;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Main BaseModelTrait that combines all model concerns.
 * 
 * This trait provides a comprehensive set of functionality for models
 * including configuration, relationships, custom columns, activity logging,
 * and utility methods.
 * 
 * @package Lyre\Strings\Model
 */
trait BaseModelTrait
{
    use CanIncludeColumns, BelongsToTenant, LogsActivity;
    use HandlesConfiguration, HandlesRelationships, HandlesCustomColumns, HandlesActivityLogging, HandlesUtilities;

    /**
     * ID column constant.
     */
    const ID_COLUMN = 'id';

    /**
     * Name column constant.
     */
    const NAME_COLUMN = 'name';

    /**
     * Status configuration constant.
     */
    const STATUS_CONFIG = 'constant.status';

    /**
     * Order column constant.
     */
    const ORDER_COLUMN = 'created_at';

    /**
     * Order direction constant.
     */
    const ORDER_DIRECTION = 'desc';
}
