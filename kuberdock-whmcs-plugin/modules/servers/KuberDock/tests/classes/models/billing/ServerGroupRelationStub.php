<?php

namespace tests\models\billing;


use models\billing\ServerGroupRelation;

class ServerGroupRelationStub extends ServerGroupRelation
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('groupid');
            $table->integer('serverid');
        };
    }
}