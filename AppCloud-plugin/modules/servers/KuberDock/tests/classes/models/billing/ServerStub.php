<?php

namespace tests\models\billing;


use models\billing\Server;

class ServerStub extends Server
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('name')->nullable();
            $table->text('ipaddress')->nullable();
            $table->text('hostname')->nullable();
            $table->decimal('monthlycost', 10, 2)->default(0);
            $table->text('type')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->text('accesshash')->nullable();
            $table->text('secure')->nullable();
            $table->tinyInteger('active')->nullable();
            $table->tinyInteger('disabled')->nullable();
        };
    }
}