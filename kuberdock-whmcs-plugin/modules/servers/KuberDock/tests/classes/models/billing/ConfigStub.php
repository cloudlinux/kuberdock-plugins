<?php

namespace tests\models\billing;


use models\billing\Config;

class ConfigStub extends Config
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->text('setting');
            $table->text('value');
            $table->timestamps();
        };
    }
}