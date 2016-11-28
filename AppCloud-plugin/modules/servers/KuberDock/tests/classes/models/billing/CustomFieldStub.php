<?php

namespace tests\models\billing;


use models\billing\CustomField;

class CustomFieldStub extends CustomField
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('type');
            $table->integer('relid');
            $table->text('fieldname');
            $table->text('adminonly');
            $table->timestamps();
        };
    }
}