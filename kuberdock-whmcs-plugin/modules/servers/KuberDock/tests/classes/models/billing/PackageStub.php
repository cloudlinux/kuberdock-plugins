<?php

namespace tests\models\billing;


use models\billing\Package;

class PackageStub extends Package
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
            $table->integer('gid');
            $table->text('name');
            $table->text('description')->nullable();
            $table->tinyInteger('hidden')->nullable();
            $table->tinyInteger('showdomainoptions')->nullable();
            $table->integer('welcomeemail')->default(0);
            $table->tinyInteger('stockcontrol')->nullable();
            $table->integer('qty')->default(0);
            $table->tinyInteger('proratabilling')->nullable();
            $table->smallInteger('proratadate')->nullable();
            $table->smallInteger('proratachargenextmonth')->nullable();
            $table->text('paytype')->nullable();
            $table->tinyInteger('allowqty')->nullable();
            $table->text('subdomain')->nullable();
            $table->text('autosetup')->nullable();
            $table->text('servertype')->nullable();
            $table->integer('servergroup');
            for ($i=1; $i<=24; $i++) {
                $table->text('configoption' . $i)->nullable();
            }
            $table->text('freedomain')->nullable();
            $table->text('freedomainpaymentterms')->nullable();
            $table->text('freedomaintlds')->nullable();
            $table->smallInteger('recurringcycles')->nullable();
            $table->integer('autoterminatedays')->nullable();
            $table->integer('autoterminateemail')->default(0);
            $table->tinyInteger('configoptionsupgrade')->nullable();
            $table->text('billingcycleupgrade')->nullable();
            $table->integer('upgradeemail')->default(0);
            $table->string('overagesenabled', 10)->nullable();
            $table->integer('overagesdisklimit')->nullable();
            $table->integer('overagesbwlimit')->nullable();
            $table->decimal('overagesdiskprice', 6, 4)->nullable();
            $table->decimal('overagesbwprice', 6, 4)->nullable();
            $table->tinyInteger('tax')->nullable();
            $table->tinyInteger('affiliateonetime')->nullable();
            $table->text('affiliatepaytype')->nullable();
            $table->decimal('affiliatepayamount', 10, 2)->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('retired')->nullable();
            $table->tinyInteger('is_featured')->nullable();
            $table->timestamps();
        };
    }
}