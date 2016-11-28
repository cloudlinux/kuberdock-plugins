<?php

namespace tests\models\billing;


use models\billing\Admin;

class AdminStub extends Admin
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->tinyInteger('roleid')->nullable();
            $table->tinyInteger('disabled')->nullable();
            $table->text('username');
        };
    }
}