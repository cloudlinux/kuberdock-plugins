<?php

namespace tests\models\billing;


use models\billing\ServerGroup;

class ServerGroupStub extends ServerGroup
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('name');
            $table->tinyInteger('filltype');
        };
    }
}