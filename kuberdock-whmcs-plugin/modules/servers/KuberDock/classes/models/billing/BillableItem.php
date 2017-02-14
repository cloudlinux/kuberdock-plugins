<?php


namespace models\billing;


use Carbon\Carbon;
use models\addon\Item;
use models\addon\resource\Pod;
use models\addon\Resources;
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

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblbillableitems';
    /**
     * @var array
     */
    protected $dates = ['duedate'];

    /**
     * @return Item
     */
    public function addonItem()
    {
        return $this->hasOne('models\addon\Item', 'billable_item_id');
    }

    /**
     * @return InvoiceItem
     */
    public function invoiceItem()
    {
        return $this->hasMany('models\billing\InvoiceItem', 'relid', 'id')->where('type', self::TYPE);
    }

    /**
     *
     */
    public function setNextDueDate()
    {
        if (!$this->duedate) {
            $this->duedate = new \DateTime();
        }
        $this->duedate = $this->duedate->modify($this->getRecurPeriod());
    }

    /**
     * @return float
     */
    public function getProRate()
    {
        $now = new Carbon();
        $daysRemain = $this->duedate->diffInDays($now);
        $periodStart = $this->duedate;
        $periodStart = $periodStart->modify(str_replace('+', '-', $this->getRecurPeriod()));
        $totalDays = $periodStart->diffInDays($this->duedate);

        return $daysRemain / $totalDays;
    }

    /**
     * @param Package $package
     * @return array (recur, recurCycle)
     * @throws \Exception
     */
    public function setRecur(Package $package)
    {
        switch ($package->getPaymentType()) {
            case 'hourly':
                throw new \Exception('Hourly payment type has no recur type');
            case 'monthly':
                $this->recur = 1;
                $this->recurcycle = self::CYCLE_MONTH;
                break;
            case 'quarterly':
                $this->recur = 3;
                $this->recurcycle = self::CYCLE_MONTH;
                break;
            case 'annually':
                $this->recur = 1;
                $this->recurcycle = self::CYCLE_YEAR;
                break;
        }
    }

    /**
     * Recalculate after payment for edited pod
     * @param Pod $pod
     * @return $this
     */
    public function recalculate(Pod $pod)
    {
        $service = $pod->getService();
        $price = $pod->getPrice();

        foreach ($pod->getPersistentDisk() as $row) {
            $resource = Resources::divided()->typePd()
                ->where('name', $row['pdName'])
                ->where('user_id', $service->userid)
                ->first();

            if (!$resource) {
                continue;
            }

            $item = Item::where('type', Resources::TYPE_PD)
                ->where('pod_id', $resource->id)
                ->where('status', Resources::STATUS_ACTIVE)
                ->first();

            if ($item) {
                $storagePrice = $row['pdSize'] * $service->package->getPricePS();
                $item->billableItem->amount = $storagePrice;
                $item->billableItem->save();
                $price -= $storagePrice;
            }
        }

        $this->amount = $price;
        $this->save();

        return $this;
    }

    /**
     * @return string
     */
    private function getRecurPeriod()
    {
        switch ($this->recurcycle) {
            case self::CYCLE_MONTH:
                $period = sprintf('+%d month', $this->recur);
                break;
            case self::CYCLE_YEAR:
                $period = sprintf('+%d year', $this->recur);
                break;
            default:
                $period = sprintf('+%d month', $this->recur);
                break;
        }

        return $period;
    }
}