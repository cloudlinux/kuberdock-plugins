<?php

namespace tests;


class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        if ($this->usesTrait(EloquentMock::class)) {
            $this->configureDatabase();
            $this->createTables();
        }

        if ($this->usesTrait(CurlMock::class)) {
            $this->setUpCurl();
        }
    }

    public function tearDown()
    {
        if ($this->usesTrait(EloquentMock::class)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    private function usesTrait($class)
    {
        return in_array($class, class_uses($this), true);
    }
}