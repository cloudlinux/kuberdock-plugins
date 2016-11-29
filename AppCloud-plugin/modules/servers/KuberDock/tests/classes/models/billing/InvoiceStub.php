<?php

namespace tests\models\billing;


use models\billing\Invoice;

class InvoiceStub extends Invoice
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
        };
    }
}