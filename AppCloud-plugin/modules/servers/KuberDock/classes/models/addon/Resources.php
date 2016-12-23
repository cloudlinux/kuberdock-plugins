<?php


namespace models\addon;


use Carbon\Carbon;
use components\InvoiceItem as ComponentsInvoiceItem;
use components\Tools;
use exceptions\CException;
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
     *
     */
    const STATUS_PAID_DELETED = 'PaidDeleted';

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
            $table->string('status', 32)->default(self::STATUS_ACTIVE);

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
     * @param null $notItemId
     * @return mixed
     */
    public function hasPaidItems($notItemId = null)
    {
        $query = Resources::select('r.*')
            ->from('KuberDock_items as i')
            ->join('KuberDock_resource_items as ri', 'ri.item_id', '=', 'i.id')
            ->join('KuberDock_resource_pods as rp', 'rp.id', '=', 'ri.resource_pod_id')
            ->join('KuberDock_resources as r', 'r.id', '=', 'rp.resource_id')
            ->where('i.due_date', '>', new Carbon());

        if ($notItemId) {
            $query->where('i.id', '!=', $notItemId);
        }

        $query->groupBy('r.id');

        return $query->get()->count();
    }

    /**
     * @param ComponentsInvoiceItem $invoiceItem
     */
    public function divide(ComponentsInvoiceItem $invoiceItem)
    {
        $addonItem = $this->resourcePods->last()->items()->first();
        $billableItem = $addonItem->billableItem;

        if (!$billableItem) {
            return;
        }

        $billableItem->amount -= $invoiceItem->getTotal();
        $billableItem->save();

        if ($this->type == self::TYPE_IP) {
            $invoiceItem->setName('#' . $this->name);
        }

        // Separate billable item
        $newBillableItem = $billableItem->replicate();
        $newBillableItem->description = $invoiceItem->getDescription();
        $newBillableItem->amount = $invoiceItem->getTotal();
        $newBillableItem->invoicecount = 0;
        $newBillableItem->save();

        // Separate addon item
        $newAddonItem = $addonItem->replicate();
        $newAddonItem->pod_id = $this->id;
        $newAddonItem->billable_item_id = $newBillableItem->id;
        $newAddonItem->type = $this->type;
        $newAddonItem->save();

        // Change resource status
        $this->update([
            'status' => self::STATUS_DIVIDED,
        ]);
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

    /**
     * @param Item $item
     */
    public function freePD(Item $item)
    {
        $pd = $item->service->getApi()->getPD();

        foreach ($pd->getData() as $v) {
            if ($v['name'] == $this->name) {
                try {
                    $item->service->getApi()->deletePD($v['id'], true);
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
            try {
                $api->deletePD($v['id'], true);
            } catch (\Exception $e) {
                CException::log($e);
            }
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
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETED;
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
        if (!$pod->getPublicIP()) {
            return;
        }

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

        // Attach resource to divided items
        $resourceItems = Item::where('type', '!=', Resources::TYPE_POD)
            ->where('user_id', $resource->user_id)
            ->where('pod_id', $resource->id)
            ->get();

        foreach ($resourceItems as $resourceItem) {
            $resourceItem->resourcePods()->attach($resourcePods);
        }
    }
}