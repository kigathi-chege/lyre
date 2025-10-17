<?php

namespace Lyre;

use Lyre\Repository\Repository as BaseRepository;

/**
 * Main Repository class for backward compatibility.
 * 
 * This class extends the new modular Repository class to maintain
 * backward compatibility while providing access to the new architecture.
 * 
 * @package Lyre
 * @deprecated Use Lyre\Repository\Repository instead
 */
class Repository extends BaseRepository
{
    // This class now extends the new modular Repository class
    // All functionality is inherited from the base class
}
