<?php

namespace Lyre\Strings\Traits;

use Lyre\Strings\Model\BaseModelTrait as BaseTrait;

/**
 * BaseModelTrait for backward compatibility.
 * 
 * This trait extends the new modular BaseModelTrait to maintain
 * backward compatibility while providing access to the new architecture.
 * 
 * @package Lyre\Strings\Traits
 * @deprecated Use Lyre\Strings\Model\BaseModelTrait instead
 */
trait BaseModelTrait
{
    use BaseTrait;

    // This trait now uses the new modular BaseModelTrait
    // All functionality is inherited from the base trait
}
