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
            $table->tinyInteger('default')->nullable();
        };
    }
}