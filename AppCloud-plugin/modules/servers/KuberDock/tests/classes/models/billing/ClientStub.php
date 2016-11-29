<?php

namespace tests\models\billing;


use models\billing\Client;

class ClientStub extends Client
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('defaultgateway');
            $table->enum('status', ['Active','Inactive','Closed']);
            $table->timestamps();
        };
    }
}