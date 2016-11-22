<?php


namespace models\addon;


use components\BillingApi;
use models\billing\BillableItem;
use models\billing\Client;
use models\billing\Invoice;
use models\billing\InvoiceItem;
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
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('service_id');
            $table->string('pod_id', 64)->nullable();
            $table->integer('billable_item_id')->nullable();
            $table->string('status', 32)->default(Resources::STATUS_ACTIVE);
            $table->string('type', 64)->default(Resources::TYPE_POD);

            $table->index('pod_id');
            $table->index('billable_item_id');
        };
    }

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

    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
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
            ->type(Resources::TYPE_POD)
            ->where('pod_id', $podId)
            ->where('status', '!=', Resources::STATUS_DELETED);
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
     * @throws \Exception
     */
    public function suspend()
    {
        BillingApi::model()->suspendModule($this->service, 'Not enough funds');
    }

    /**
     * @throws \Exception
     */
    public function stopInvoicing()
    {
        if (!$this->billableItem) {
            return;
        }

        $unpaidInvoices = $this->invoices()->unpaid()->get();

        foreach ($unpaidInvoices as $itemInvoice) {
            $invoiceItemCount = InvoiceItem::where('invoiceid', $itemInvoice->invoice_id)
                ->where('type', BillableItem::TYPE)
                ->count();

            if ($invoiceItemCount > 1) {
                $invoiceItem = InvoiceItem::where('invoiceid', $itemInvoice->invoice_id)
                    ->where('relid', $this->billableItem->id)
                    ->where('type', BillableItem::TYPE)
                    ->first();

                BillingApi::request('updateinvoice', [
                    'invoiceid' => $itemInvoice->invoice_id,
                    'deletelineids' => [
                        'invoice_item_id' => $invoiceItem->id,
                    ],
                ]);
            } else {
                $itemInvoice->invoice->status = Invoice::STATUS_CANCELLED;
                $itemInvoice->invoice->save();
                $itemInvoice->status = Invoice::STATUS_CANCELLED;
                $itemInvoice->save();
            }
        }

        ResourcePods::where('item_id', $this->id)->delete();

        $this->billableItem->delete();
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
     *
     * When user buys product or product+PA at first time
     *
     * @param Invoice $invoice
     */
    public function invoiceCorrection(Invoice $invoice)
    {
        $service = Service::typeKuberDock()->whereHas('invoiceItem', function ($query) use ($invoice) {
            $query->where('invoiceid', $invoice->id);
        })->first();

        if (!$service) {
            return;
        }

        // AC-3839 Add recurring price to invoice
        // Add setup\recurring funds only for newly created service

        $package = $service->package;
        $invoiceItems = $package->getBilling()->firstInvoiceCorrection($service);

        $currency = $invoice->client->currencyModel->id;
        $pricing = $package->pricing()->withCurrency($currency)->first()->getReadable();
        if ($pricing['setup'] > 0) {
            $invoiceItems->add($package->createInvoiceItem('Setup', $pricing['setup']));
        }
        if ($pricing['recurring'] > 0) {
            $invoiceItems->add(
                $package->createInvoiceItem('Recurring ('. $pricing['cycle'] .')', $pricing['recurring'])
            );
        }

        if ($invoiceItems->sum() <= 0) {
            return;
        }

        $invoice = $invoice->edit($invoiceItems);

        // In order to system know that it is product order invoice
        $invoiceItem = $invoice->items->first();
        $invoiceItem->type = 'Hosting';
        $invoiceItem->relid = $service->id;
        $invoiceItem->save();
    }
}