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

    /**
     * @return Invoice
     */
    public function invoice()
    {
        return $this->belongsTo('models\billing\Invoice', 'invoiceid');
    }
}