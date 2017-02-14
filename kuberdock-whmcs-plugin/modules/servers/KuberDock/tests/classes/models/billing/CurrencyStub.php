<?php

namespace tests\models\billing;


use models\billing\Currency;

class CurrencyStub extends Currency
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('code')->default('');
            $table->text('prefix')->default('');
            $table->text('suffix')->default('');
            $table->tinyInteger('format')->default(1);
            $table->decimal('rate', 10, 5)->default(1);
            $table->tinyInteger('default')->nullable();
        };
    }
}