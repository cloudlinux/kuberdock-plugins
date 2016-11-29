<?php

namespace tests\models\billing;


use models\billing\InvoiceItem;

class InvoiceItemStub extends InvoiceItem
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('invoiceid');
            $table->string('type', 30);
            $table->integer('relid');
        };
    }
}