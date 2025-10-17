<?php

namespace Lyre;

use Lyre\Resource\Resource as BaseResource;

/**
 * Main Resource class for backward compatibility.
 * 
 * This class extends the new modular Resource class to maintain
 * backward compatibility while providing access to the new architecture.
 * 
 * @package Lyre
 * @deprecated Use Lyre\Resource\Resource instead
 */
class Resource extends BaseResource
{
    // This class now extends the new modular Resource class
    // All functionality is inherited from the base class
}
