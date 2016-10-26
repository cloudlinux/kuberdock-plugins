<?php


namespace models\addon;


use base\models\CL_Invoice;
use components\BillingApi;
use models\billing\BillableItem;
use models\billing\Client;
use models\billing\Config;
use models\billing\Invoice;
use models\billing\Package;
use models\billing\Service;
use models\Model;

class Item extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_items';
    /**
     * @var array
     */
    protected $fillable = ['pod_id', 'user_id', 'service_id', 'status', 'type'];

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();
    }

    /**
     * @return Service
     */
    public function service()
    {
        return $this->belongsTo('models\billing\Service', 'service_id');
    }

    /**
     * @return BillableItem
     */
    public function billableItem()
    {
        return $this->hasOne('models\billing\BillableItem', 'id', 'billable_item_id');
    }

    /**
     * @return ItemInvoice
     */
    public function invoices()
    {
        return $this->hasMany('models\addon\ItemInvoice', 'item_id', 'id');
    }

    /**
     * @return ResourcePods
     */
    public function resourcePods()
    {
        return $this->hasMany('models\addon\ResourcePods', 'item_id');
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->belongsTo('models\billing\Client', 'user_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePayg($query)
    {
        return $query->where('billable_item_id', 0)->orWhereNull('billable_item_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeFixed($query)
    {
        return $query->where('billable_item_id', '>', 0);
    }

    /**
     * Get not deleted items by pod id
     * @param $query
     * @param $podId
     * @return mixed
     */
    public function scopeWithPod($query, $podId)
    {
        return $query
            ->where('status', '!=', Resources::STATUS_DELETED)
            ->where('type', Resources::TYPE_POD)
            ->where('pod_id', $podId)->first();
    }

    /**
     * @return bool
     */
    public function isPersistentStorage()
    {
        return $this->status == Resources::TYPE_PD;
    }

    /**
     * @return bool
     */
    public function isIP()
    {
        return $this->status == Resources::TYPE_IP;
    }

    /**
     * @return bool
     */
    public function isPod()
    {
        return $this->status == Resources::TYPE_POD;
    }

    /**
     *
     */
    public function suspend()
    {
        BillingApi::model()->suspendModule($this->service, 'Not enough funds');
    }

    /**
     *
     */
    public function stopInvoicing()
    {
        // Stop invoicing
        $this->billableItem->invoiceItem()->update([
            'status' => Invoice::STATUS_CANCELLED,
        ]);
        $this->invoices()->update([
            'status' => Invoice::STATUS_CANCELLED,
        ]);

        $this->status = Resources::STATUS_DELETED;
        $this->save();
    }

    /**
     * After newly generated invoice for billable item, add record to KuberDock_items (Items)
     * @param int $invoiceId
     */
    public function handleInvoicing($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $invoiceItems = $invoice->items()
            ->where('relid', '>', 0)
            ->where('invoiceid', '>', 0)
            ->where('type', BillableItem::TYPE)
             ->get();

        foreach ($invoiceItems as $invoiceItem) {
            $addonItem = $invoiceItem->billableItem->addonItem;

            if ($addonItem) {
                $addonItem->invoices()->save(new ItemInvoice([
                    'invoice_id' => $invoice->id,
                    'status' => $invoice->status,
                    'type' => ItemInvoice::TYPE_ORDER,
                ]));
            }
        }
    }

    /**
     * For product order invoice upgrade items accordingly to PA
     * @param Invoice $invoice
     */
    public function invoiceCorrection(Invoice $invoice)
    {
        $app = new App();
        $app = $app->getFromSession();

        $service = Service::typeKuberDock()->whereHas('invoiceItem', function ($query) use ($invoice) {
            $query->where('invoiceid', $invoice->id);
        })->first();

        if ($service && $app) {
            // AC-3839 Add recurring price to invoice
            // Add setup\recurring funds only for newly created service
            $package = $service->package;
            /* @var Package $package */

            $pricing = $package->pricing()->withCurrency($invoice->client->currencyModel->id)->first()->getReadable();

            $invoiceItems = $app->getResource()->getInvoiceItems();

            if ($pricing['setup'] > 0) {
                $invoiceItems->add($package->createInvoiceItem('Setup', $pricing['setup ']));
            }

            if ($pricing['recurring'] > 0) {
                $invoiceItems->add(
                    $package->createInvoiceItem('Recurring ('. $pricing['cycle'] .')', $pricing['recurring'])
                );
            }

            if (($firstDeposit = $package->getFirstDeposit())) {
                $invoiceItems->add($package->createInvoiceItem('First deposit', $firstDeposit)->setTaxed(false));
            }

            if ($invoiceItems->sum() <= 0) {
                return;
            }

            $invoice = $invoice->edit($invoiceItems);

            // In order to system know that it is product order invoice
            $invoice->items->first()->setRawAttributes(array(
                'type' => 'Hosting',
                'relid' => $service->id,
            ))->save();
        }
    }
}