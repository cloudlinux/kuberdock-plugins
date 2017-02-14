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
            $table->integer('userid');
            $table->text('description');
            $table->smallInteger('recur')->default(0);
            $table->text('recurcycle');
            $table->smallInteger('recurfor')->default(0);
            $table->tinyInteger('invoiceaction');
            $table->decimal('amount', 10, 2);
            $table->smallInteger('invoicecount')->default(0);
            $table->date('duedate');
        };
    }
}