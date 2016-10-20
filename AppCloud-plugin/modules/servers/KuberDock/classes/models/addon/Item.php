<?php


namespace models\addon;


use base\models\CL_Invoice;
use models\billing\BillableItem;
use models\billing\Client;
use models\billing\Config;
use models\billing\Invoice;
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
    public function stopInvoicing()
    {
        // Stop invoicing
        $this->billableItem->invoiceaction = BillableItem::CREATE_NO_INVOICE_ID;
        $this->billableItem->description .= ' (Deleted)';
        $this->billableItem->save();

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
            $service->package->getBilling()->invoiceCorrection($app->getResource(), $service, $invoice);
        }
    }
}