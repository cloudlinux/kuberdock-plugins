<?php

namespace tests\models\billing;


use models\billing\PackageGroup;

class PackageGroupStub extends PackageGroup
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
        };
    }
}