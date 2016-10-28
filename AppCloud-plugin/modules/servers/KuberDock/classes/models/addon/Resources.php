<?php


namespace models\addon;


use components\InvoiceItem as ComponentsInvoiceItem;
use exceptions\NotFoundException;
use models\addon\resource\Pod;
use models\billing\Invoice;
use models\billing\Service;
use models\Model;

class Resources extends Model
{
    /**
     *
     */
    const TYPE_POD = 'Pod';
    /**
     *
     */
    const TYPE_PD = 'Storage';
    /**
     *
     */
    const TYPE_IP = 'IP';
    /**
     *
     */
    const STATUS_ACTIVE = 'Active';
    /**
     *
     */
    const STATUS_DELETED = 'Deleted';
    /**
     *
     */
    const STATUS_ERROR = 'Error';
    /**
     *
     */
    const STATUS_DIVIDED  = 'Divided';
    /**
     *
     */
    const STATUS_SUSPENDED = 'Suspended';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_resources';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'billable_item_id', 'name', 'type', 'status'];

    /**
     * @param Pod $pod
     * @param Item $item
     */
    public static function add(Pod $pod, Item $item)
    {
        $resource = new static();
        $resource->addPD($pod, $item);
        $resource->addIP($pod, $item);
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_IP,
            self::TYPE_PD,
            self::TYPE_POD,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resourcePods()
    {
        return $this->hasMany('models\addon\ResourcePods', 'resource_id');
    }

    /**
     * @param $query
     * @param $userId
     * @return mixed
     */
    public function scopeNotDeleted($query, $userId)
    {
        return $query->where('user_id', $userId)->where('status', '!=', self::STATUS_DELETED);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypePd($query)
    {
        return $query->where('type', self::TYPE_PD);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeIp($query)
    {
        return $query->where('type', self::TYPE_IP);
    }

    /**
     * @param ComponentsInvoiceItem $item
     * @return bool
     */
    public function divide(ComponentsInvoiceItem $item)
    {
        $addonItem = $this->resourcePods()->first()->item;
        $billableItem = $addonItem->billableItem;

        if (!$billableItem) {
            return true;
        }

        $billableItem->amount -= $item->getTotal();
        $billableItem->save();

        if ($this->type == self::TYPE_IP) {
            $item->setName('#' . $this->name);
        }

        // Separate billable item
        $newBillableItem = $billableItem->replicate();
        $newBillableItem->description = $item->getDescription();
        $newBillableItem->amount = $item->getTotal();
        $newBillableItem->invoicecount = 0;
        $newBillableItem->save();

        // Separate addon item
        $newAddonItem = $addonItem->replicate();
        $newAddonItem->pod_id = null;
        $newAddonItem->billable_item_id = $newBillableItem->id;
        $newAddonItem->type = $this->type;
        $newAddonItem->save();

        $this->resourcePods()->save(ResourcePods::firstOrNew([
            'resource_id' => $this->id,
            'item_id' => $newAddonItem->id,
        ]));

        // Change resource status
        $this->update([
            'status' => self::STATUS_DIVIDED,
        ]);

        return false;
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @return ItemInvoice[]
     */
    public function getUnpaidItemInvoices(ItemInvoice $itemInvoice)
    {
        $query = ItemInvoice::select('KuberDock_item_invoices.*')
            ->join('KuberDock_resource_pods as rp2', 'rp2.item_id', '=', 'KuberDock_item_invoices.item_id')
            ->join('KuberDock_resource_pods as rp1', 'rp1.resource_id', '=', 'rp2.resource_id')
            ->join('KuberDock_resources', 'KuberDock_resources.id', '=', 'rp2.resource_id')
            ->where('KuberDock_item_invoices.status', Invoice::STATUS_UNPAID)
            ->where('KuberDock_resources.status', '!=', self::STATUS_DELETED)
            ->where('KuberDock_item_invoices.invoice_id', '!=', $itemInvoice->invoice_id)
            ->groupBy('KuberDock_item_invoices.item_id');

        if ($itemInvoice->item->pod_id) {
            $query->where('rp1.pod_id', $itemInvoice->item->pod_id);
        } else {
            $query->where('rp1.item_id', $itemInvoice->item_id);
        }

        return $query->get();
    }

    /**
     * @param Item $item
     */
    public function freePD(Item $item)
    {
        $pd = $item->service->getApi()->getPD();

        foreach ($pd->getData() as $v) {
            if ($v['name'] == $this->name) {
                try {
                    $item->service->getApi()->deletePD($v['id']);
                } catch (\Exception $e) {
                    \exceptions\CException::log($e);
                }
            }
        }

        $this->update([
            'status' => self::STATUS_DELETED,
        ]);
    }

    /**
     * @param Item $item
     */
    public function freeIP(Item $item)
    {
        $resourcePods = $this->resourcePods()->whereNotNull('pod_id')->groupBy('pod_id')->get();

        foreach ($resourcePods as $resourcePod) {
            try {
                $item->service->getApi()->unbindIP($resourcePod->pod_id);
            } catch (NotFoundException $e) {
                continue;
            }
        }

        $this->update([
            'status' => self::STATUS_DELETED,
        ]);
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function freeAll(Service $service)
    {
        $api = $service->getApi();
        $pd = $api->getPD()->getData();

        foreach ($pd as $v) {
            $api->deletePD($v['id']);
        }

        foreach ($api->getPods()->getData() as $pod) {
            try {
                $service->getAdminApi()->unbindIP($pod['id']);
            } catch (NotFoundException $e) {
                continue;
            }
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isDivided()
    {
        return $this->status == self::STATUS_DIVIDED;
    }

    /**
     * @param Pod $pod
     * @param Item $item
     */
    private function addPD(Pod $pod, Item $item)
    {
        foreach ($pod->getPersistentDisk() as $pd) {
            $this->addResource($pd['pdName'], $pod, $item, self::TYPE_PD);
        }
    }

    /**
     * @param Pod $pod
     * @param Item $item
     */
    private function addIP(Pod $pod, Item $item)
    {
        $ipPoolData = $pod->getService()->getApi()->getIpPoolStat()->getData();

        if (!$ipPoolData) {
            return;
        }

        $this->addResource(count($ipPoolData), $pod, $item, self::TYPE_IP);
    }

    /**
     * @param string $name
     * @param Pod $pod
     * @param Item $item
     * @param $type
     */
    private function addResource($name, Pod $pod, Item $item, $type)
    {
        $resource = Resources::firstOrCreate([
            'user_id' => $pod->getService()->userid,
            'name' => $name,
            'type' => $type,
        ]);

        $resourcePods = ResourcePods::firstOrNew(array(
            'pod_id' => $pod->id,
            'resource_id' => $resource->id,
            'item_id' => $item->id,
        ));
        $resource->resourcePods()->save($resourcePods);
    }
}