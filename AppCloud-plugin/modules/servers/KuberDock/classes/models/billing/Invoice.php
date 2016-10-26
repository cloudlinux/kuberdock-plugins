<?php


namespace models\billing;


use components\BillingApi;
use components\InvoiceItemCollection;
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
     *
     */
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblinvoices';
    /**
     * @var array
     */
    protected $fillable = ['status'];
    /**
     * @var array
     */
    protected $dates = ['duedate'];

    /**
     * @param array $dates
     */
    public function setDates($dates)
    {
        $this->dates = $dates;
    }

    /**
     * @return InvoiceItem
     */
    public function items()
    {
        return $this->hasMany('models\billing\InvoiceItem', 'invoiceid');
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->belongsTo('models\billing\Client', 'userid');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return 'viewinvoice.php?id=' . $this->id;
    }

    /**
     * @param InvoiceItemCollection $invoiceItems
     * @return Invoice
     */
    public function edit(InvoiceItemCollection $invoiceItems)
    {
        return BillingApi::model()->editInvoice($this, $invoiceItems);
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

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->status == self::STATUS_PAID;
    }

    /**
     * @return bool
     */
    public function isUnpaid()
    {
        return $this->status == self::STATUS_UNPAID;
    }

    /**
     * Add first deposit
     * @return $this
     */
    public function addFirstDeposit()
    {
        foreach ($this->items as $row) {
            if (stripos($row->description, self::FIRST_DEPOSIT_DESCRIPTION) !== false && $row->amount) {
                BillingApi::model()->addCredit($row);
            }
        }

        return $this;
    }
}