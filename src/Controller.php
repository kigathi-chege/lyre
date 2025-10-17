<?php

namespace Lyre;

use Lyre\Controller\Controller as BaseController;

/**
 * Main Controller class for backward compatibility.
 * 
 * This class extends the new modular Controller class to maintain
 * backward compatibility while providing access to the new architecture.
 * 
 * @package Lyre
 * @deprecated Use Lyre\Controller\Controller instead
 */
class Controller extends BaseController
{
    // This class now extends the new modular Controller class
    // All functionality is inherited from the base class
}
