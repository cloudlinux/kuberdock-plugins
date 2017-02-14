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
            $table->integer('userid');
            $table->string('type', 30)->nullable();
            $table->integer('relid')->nullable();
            $table->decimal('amount');
            $table->string('description');
        };
    }
}