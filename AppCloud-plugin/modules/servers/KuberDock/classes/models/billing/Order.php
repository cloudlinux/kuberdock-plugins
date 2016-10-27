<?php


namespace models\billing;


use models\Model;

class Order extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblorders';
}