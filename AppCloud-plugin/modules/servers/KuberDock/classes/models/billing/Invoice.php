<?php


namespace models\billing;


use models\Model;

class Invoice extends Model
{
    /**
     * It seems, it is used if user upgrades or downgrades a product and there is a deposit in new product
     */
    const CUSTOM_INVOICE_DESCRIPTION = 'Custom invoice';
    /**
     *
     */
    const FIRST_DEPOSIT_DESCRIPTION = 'First deposit';

    /**
     *
     */
    const STATUS_PAID = 'Paid';
    /**
     *
     */
    const STATUS_UNPAID = 'Unpaid';
    /**
     *
     */
    const STATUS_DELETED = 'Deleted';

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblinvoices';

    /**
     * @return InvoiceItem
     */
    public function items()
    {
        return $this->hasMany('models\billing\InvoiceItem', 'invoiceid');
    }

    /**
     * @return bool
     */
    public function isOverdue()
    {
        $config = Config::get();

        $dueDate = new \DateTime($this->duedate);
        $currentDate = new \DateTime();
        $daysLeft = (int) $dueDate->diff($currentDate)->format('%R%a');

        return $daysLeft >= $config->AutoSuspensionDays;
    }
}