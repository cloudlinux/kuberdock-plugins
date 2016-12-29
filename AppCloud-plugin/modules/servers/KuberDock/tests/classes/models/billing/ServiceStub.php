<?php

namespace tests\models\billing;


use models\billing\Service;

class ServiceStub extends Service
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
            $table->integer('orderid')->nullable();
            $table->integer('packageid');
            $table->integer('server');
            $table->text('username');
            $table->text('domain');
            $table->enum('domainstatus', ['Pending','Active','Suspended','Terminated','Cancelled','Fraud']);
            $table->date('regdate')->nullable();
            $table->date('nextduedate')->nullable();
            $table->timestamps();
        };
    }
}