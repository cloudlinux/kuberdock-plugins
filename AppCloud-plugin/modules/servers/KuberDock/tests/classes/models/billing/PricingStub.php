<?php

namespace tests\models\billing;


use models\billing\Pricing;

class PricingStub extends Pricing
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->enum('type', ['product','addon','configoptions','domainregister','domaintransfer','domainrenew','domainaddons']);
            $table->integer('currency');
            $table->integer('relid');
            $table->decimal('msetupfee', 10, 2);
            $table->decimal('qsetupfee', 10, 2);
            $table->decimal('ssetupfee', 10, 2)->nullable();
            $table->decimal('asetupfee', 10, 2);
            $table->decimal('bsetupfee', 10, 2);
            $table->decimal('tsetupfee', 10, 2);

            $table->decimal('monthly', 10, 2);
            $table->decimal('quarterly', 10, 2);
            $table->decimal('semiannually', 10, 2);
            $table->decimal('annually', 10, 2);
            $table->decimal('biennially', 10, 2);
            $table->decimal('triennially', 10, 2);
        };
    }
}