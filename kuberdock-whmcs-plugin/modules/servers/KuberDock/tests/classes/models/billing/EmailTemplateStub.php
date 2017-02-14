<?php

namespace tests\models\billing;


use models\billing\EmailTemplate;

class EmailTemplateStub extends EmailTemplate
{
    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->text('name');
            $table->text('subject');
            $table->text('type');
            $table->text('message');
            $table->timestamps();
        };
    }
}