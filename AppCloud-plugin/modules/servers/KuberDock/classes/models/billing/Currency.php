<?php


namespace models\billing;


use models\Model;

class Currency extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblcurrencies';
}