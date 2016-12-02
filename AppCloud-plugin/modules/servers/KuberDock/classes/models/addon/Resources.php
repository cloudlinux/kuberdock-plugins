<?php


namespace models\addon;


use components\InvoiceItem as ComponentsInvoiceItem;
use components\Tools;
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
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->enum('type', array(
                Resources::TYPE_IP,
                Resources::TYPE_PD,
            ));
            $table->string('status', 32)->default(\models\addon\Resources::STATUS_ACTIVE );

            $table->index('name');
        };
    }

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
     * @param string|null $podId
     * @return bool
     */
    public function divide(ComponentsInvoiceItem $item, $podId = null)
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

        $newAddonItem->resourcePods()->attach(ResourcePods::firstOrNew([
            'resource_id' => $this->id,
            'pod_id' => $podId,
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
    public static function getUnpaidItemInvoices(ItemInvoice $itemInvoice)
    {
        $query = ItemInvoice::select('i.*')
            ->from('KuberDock_item_invoices as i')
            ->join('KuberDock_resource_items as ri', 'ri.item_id', '=', 'i.item_id')
            ->join('KuberDock_resource_pods as rp', 'rp.id', '=', 'ri.resource_pod_id')
            ->join('KuberDock_resources as r', 'r.id', '=', 'rp.resource_id')
            ->where('i.status', Invoice::STATUS_UNPAID)
            ->where('r.status', '=', self::STATUS_DIVIDED)
            ->where('i.invoice_id', '!=', $itemInvoice->invoice_id)
            ->groupBy('i.item_id');

        if ($itemInvoice->item->pod_id) {
            $query->where('rp.pod_id', $itemInvoice->item->pod_id);
        } else {
            $query->where('ri.item_id', $itemInvoice->item_id);
        }

        return $query->get();
    }

    public static function redirectToUnpaidInvoice($itemInvoice)
    {
        $unpaidItemInvoices = self::getUnpaidItemInvoices($itemInvoice);

        if ($unpaidItemInvoices->count()) {
            Tools::jsRedirect($unpaidItemInvoices->first()->invoice->getUrl());
        }
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
     * @return string
     */
    public function getPodId()
    {
        return $this->resourcePods()->first()->pod_id;
    }

    /**
     * @param string|null $pod_id
     * @return bool
     */
    public function isSamePod($pod_id)
    {
        return !is_null($pod_id) && $this->getPodId() == $pod_id;
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

        $resourcePods = ResourcePods::firstOrNew([
            'pod_id' => $pod->id,
            'resource_id' => $resource->id,
        ]);
        $resource->resourcePods()->save($resourcePods);
        $item->resourcePods()->attach($resourcePods);
    }
}