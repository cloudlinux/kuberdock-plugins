<?php

namespace tests\models\billing;


use models\billing\CustomFieldValue;

class CustomFieldValueStub extends CustomFieldValue
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('fieldid');
            $table->integer('relid');
            $table->text('value')->nullable();
        };
    }
}