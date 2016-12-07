<?php

namespace tests\models\billing;


use models\billing\BillableItem;

class BillableItemStub extends BillableItem
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');

        };
    }
}