<?php


namespace models\addon;


use base\models\CL_Invoice;
use models\billing\BillableItem;
use models\billing\Config;
use models\billing\Invoice;
use models\billing\Service;
use models\Model;

class Items extends Model
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
    protected function bootIfNotBooted()
    {
        parent::bootIfNotBooted();
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
     * @return Invoice
     */
    public function invoice()
    {
        return $this->hasOne('models\billing\Invoice', 'id', 'invoice_id');
    }

    /**
     * @return App
     */
    public function app()
    {
        return $this->hasOne('models\addon\App', 'id', 'app_id');
    }

    /**
     * @return Resources
     */
    public function resources()
    {
        return $this->hasOne('models\addon\Resources', 'billable_item_id', 'billable_item_id');
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
     * Get paid items
     * @param $query
     * @return mixed
     */
    public function scopePaid($query)
    {
        return $query->where('status', CL_Invoice::STATUS_PAID);
    }

    /**
     * Get unpaid items
     * @param $query
     * @return mixed
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', CL_Invoice::STATUS_UNPAID);
    }

    /**
     * @return bool
     */
    public function isUnpaid()
    {
        return $this->status == Invoice::STATUS_UNPAID;
    }

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->status == Invoice::STATUS_PAID;
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
     * After newly generated invoice for billable item, add record to KuberDock_items (Items)
     * @param int $invoiceId
     */
    public function handleInvoicing($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $invoiceItems = $invoice->items()
            ->where('relid', '>', 0)
            ->where('invoiceid', '>', 0)
            ->where('type', BillableItem::TYPE)->get();

        foreach ($invoiceItems as $invoiceItem) {
            // TODO: refactor to one to many
            $item = $invoiceItem->billableItem->addonItem()->orderBy('id', 'desc')->first();

            if ($item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $invoice->id;
                $newItem->status = $invoice->status;
                $newItem->save();
            }
        }
    }

    /**
     * Set unpaid resources\pods
     */
    public function setUnpaid()
    {
        if (!$this->billableItem) {
            return;
        }

        if ($this->invoice->isOverdue()) {
            if (!$this->service) {
                return;
            }

            // Stop pod and set unpaid, when one of resources or pod is unpaid
            if ($this->resources->resourcePods) {
                foreach ($this->resources->resourcePods as $resourcePod) {
                    $this->service->getPod()->setUnpaid($resourcePod->pod_id);
                }
            } else {
                $this->service->getPod()->setUnpaid($this->pod_id);
            }

            // Free resources
            switch ($this->type) {
                case Resources::TYPE_POD:
                    // TODO: remove PD\IP?
                    break;
                case Resources::TYPE_PD:
                    $this->resources->freePD($this);
                    break;
                case Resources::TYPE_IP:
                    $this->resources->freeIP($this);
                    break;
            }

            // Stop invoicing
            $this->billableItem->invoiceaction = BillableItem::CREATE_NO_INVOICE_ID;
            $this->billableItem->save();

            $this->status = Resources::STATUS_DELETED;
            $this->save();
        }
    }

    /**
     * Mark items as Paid, search unpaid resources, if not found then run pod
     * @param int $invoiceId
     */
    public function startPodAfterPayment($invoiceId)
    {
        $items = Items::unpaid()->where('invoice_id', $invoiceId)->get();

        Items::unpaid()->where('invoice_id', $invoiceId)->update(array(
            'status' => Invoice::STATUS_PAID,
        ));

        // TODO: refactor
        $product = \KuberDock_Product::model();

        // Start unpaid pods
        foreach ($items as $item) {
            /* @var Items $item */
            if (!$item->resources) {
                $product->startPodAndRedirect($item->service->id, $item->pod_id, true);
            }

            switch ($item->type) {
                case Resources::TYPE_POD:
                    $invoiceId = $item->resources->getUnpaidInvoices($item);
                    break;
                case Resources::TYPE_PD:
                case Resources::TYPE_IP:
                    foreach ($item->resources->resourcePods as $resourcePod) {
                        $invoiceId =  $item->resources->getUnpaidInvoices($resourcePod->pod_id);
                        if ($invoiceId) {
                            break;
                        }
                    }
                    break;
            }
            // TODO: probably, remove such func from other places
            if (!$invoiceId) {
                $product->startPodAndRedirect($item->service->id, $item->pod_id, true);
            }
        }
    }
}