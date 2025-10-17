<?php

namespace Lyre\Tests\Unit;

use Lyre\Tests\TestCase;
use Lyre\Facades\Lyre;

class LyreFacadeTest extends TestCase
{
    public function test_facade_can_be_resolved()
    {
        $this->assertInstanceOf(\Lyre\Services\ModelService::class, Lyre::model());
    }

    public function test_database_service_can_be_resolved()
    {
        $this->assertInstanceOf(\Lyre\Services\Database\DatabaseService::class, Lyre::database());
    }

    public function test_response_service_can_be_resolved()
    {
        $this->assertInstanceOf(\Lyre\Services\Response\ResponseService::class, Lyre::response());
    }

    public function test_validation_service_can_be_resolved()
    {
        $this->assertInstanceOf(\Lyre\Services\Validation\ValidationService::class, Lyre::validation());
    }
}
