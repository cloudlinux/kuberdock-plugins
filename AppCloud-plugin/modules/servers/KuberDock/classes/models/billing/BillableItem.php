<?php


namespace models\billing;


use models\addon\Items;
use models\Model;

class BillableItem extends Model
{
    const TYPE = 'Item';

    const CYCLE_DAY = 'Days';
    const CYCLE_WEEK = 'Weeks';
    const CYCLE_MONTH = 'Months';
    const CYCLE_YEAR = 'Years';

    const CREATE_NO_INVOICE = 'noinvoice';
    const CREATE_INVOICE_NEXT_CRON = 'nextcron';
    const CREATE_NEXT_INVOICE = 'nextinvoice';
    const CREATE_DUE_DATE = 'duedate';
    const CREATE_RECUR = 'recur';

    const CREATE_NO_INVOICE_ID = '0';
    const CREATE_INVOICE_NEXT_CRON_ID = '1';
    const CREATE_NEXT_INVOICE_ID = '2';
    const CREATE_DUE_DATE_ID = '3';
    const CREATE_RECUR_ID = '4';

    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblbillableitems';

    /**
     * @return Items
     */
    public function addonItem()
    {
        return $this->hasMany('models\addon\Items', 'billable_item_id');
    }

    /**
     * @return InvoiceItem
     */
    public function invoiceItem()
    {
        return $this->hasMany('models\billing\InvoiceItem', 'relid', 'id')->where('type', self::TYPE);
    }
}