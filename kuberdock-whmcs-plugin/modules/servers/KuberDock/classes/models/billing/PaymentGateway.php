<?php


namespace models\billing;


use models\Model;

class PaymentGateway extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblpaymentgateways';
}