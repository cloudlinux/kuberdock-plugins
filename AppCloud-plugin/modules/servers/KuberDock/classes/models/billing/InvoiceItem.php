<?php


namespace models\billing;


use models\Model;

class InvoiceItem extends Model
{

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblinvoiceitems';

    /**
     * @return BillableItem
     */
    public function billableItem()
    {
        return $this->hasOne('models\billing\BillableItem', 'id', 'relid');
    }
}