<?php


namespace models\addon;


use base\models\CL_BillableItems;
use components\KuberDock_InvoiceItem;
use components\Pod;
use exceptions\CException;
use models\billing\BillableItem;
use models\billing\Invoice;
use models\Model;
use KuberDock_Pod;
use KuberDock_Hosting;

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
    protected $fillable = array('user_id', 'billable_item_id', 'name', 'type', 'status');

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();
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
     * @param $name
     * @param $userId
     * @return mixed
     */
    public function scopeByName($query, $name, $userId)
    {
        return $query->where('user_id', $userId)->where('name', $name)->where('status', '!=', self::STATUS_DELETED);
    }

    /**
     * @param string $podId
     */
    public static function add($podId)
    {
        $item = Items::paid()->where('pod_id', $podId)->orderBy('id', 'desc')->first();
        $pod = $item->service->getPod()->load($podId);

        self::addPD($pod, $item->service->userid);
        self::addIP($pod, $item->service->userid);
    }

    /**
     * @param KuberDock_InvoiceItem $item
     */
    public function divide(KuberDock_InvoiceItem $item)
    {
        $billableItem = BillableItem::find($this->billable_item_id);

        // Separate billable item
        $newBillableItem = $billableItem->replicate();
        $newBillableItem->description = sprintf('%s - %s', $item->getType(), $item->getName());
        $newBillableItem->amount = $item->getTotal();
        $newBillableItem->invoicecount = 0;
        $newBillableItem->save();

        // Separate addon item
        // Update method updates all records
        // TODO: refactor to one to many
        $newAddonItem = $billableItem->addonItem()->orderBy('id', 'desc')->first()->replicate();
        $newAddonItem->pod_id = null;
        $newAddonItem->billable_item_id = $newBillableItem->id;
        $newAddonItem->invoice_id = 0;
        $newAddonItem->status = Invoice::STATUS_PAID;
        $newAddonItem->type = $this->type;
        $newAddonItem->save();

        // Change resource status
        $this->update(array(
            'status' => self::STATUS_DIVIDED,
            'billable_item_id' => $newBillableItem->id,
        ));

        // Reduce item amount for current item
        $billableItem->amount = $item->getTotal();
        $billableItem->save();
    }

    /**
     * @param string $podId
     * @return int|null
     */
    public function getUnpaidInvoices($podId)
    {
        $db = Items::resolveConnection();

        $items = $db->table('KuberDock_items')
            ->select('KuberDock_items.*', $db->raw('NULL AS resource_pod_id'))
            ->where('status', Invoice::STATUS_UNPAID)
            ->where('pod_id', $podId)
            ->where('user_id', $this->user_id);

        $resourceItems = $db->table('KuberDock_items')
            ->select('KuberDock_items.*', 'KuberDock_resource_pods.pod_id AS resource_pod_id')
            ->join('KuberDock_resources', 'KuberDock_items.billable_item_id', '=', 'KuberDock_resources.billable_item_id')
            ->join('KuberDock_resource_pods', 'KuberDock_resource_pods.resource_id', '=', 'KuberDock_resources.id')
            ->where('KuberDock_resource_pods.pod_id', $podId)
            ->where('KuberDock_items.user_id', $this->user_id)
            ->where('KuberDock_items.status', Invoice::STATUS_UNPAID);

        $data = $resourceItems->union($items)->get();

        foreach ($data as $row) {
            return $row->invoice_id;
        }

        return null;
    }

    /**
     * @param Items $item
     */
    public function freePD($item)
    {
        $pdName = $this->getNameFromDescription($item->billableItem->description);
        $pd = $item->service->getApi()->getPD();

        foreach ($pd->getData() as $v) {
            if ($v['name'] == $pdName) {
                try {
                    $item->service->getApi()->deletePD($v['id']);
                    $model = Resources::byName($pdName, $item->service->userid);
                    if ($model) {
                        $model->status = self::STATUS_DELETED;
                        $model->save();
                    }
                } catch (\Exception $e) {
                    \exceptions\CException::log($e);
                }
            }
        }
    }

    /**
     * @param Items $item
     */
    public function freeIP($item)
    {
        $ip = $this->getNameFromDescription($item->billableItem->description);

        try {
            foreach ($this->resourcePods as $resourcePod) {
                try {
                    $item->service->getApi()->stopPod($resourcePod->pod_id);
                } catch (\Exception $e) {
                    // pass
                }
                $item->service->getApi()->unbindIP($resourcePod->pod_id);
                $model = Resources::byName($ip, $item->service->userid);
                if ($model) {
                    $model->status = self::STATUS_DELETED;
                    $model->save();
                }
            }
        } catch (\Exception $e) {
            CException::log($e);
        }
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return array(
            self::TYPE_IP,
            self::TYPE_PD,
            self::TYPE_POD,
        );
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
     * @param $userId
     */
    private function addPD(Pod $pod, $userId)
    {
        foreach ($pod->getPersistentDisk() as $pd) {
            $model = Resources::byName($pd['pdName'], $userId)->first();
            if (!$model) {
                $item = Items::withPod($pod->id);
                $model = Resources::create(array(
                    'user_id' => $userId,
                    'name' => $pd['pdName'],
                    'type' => self::TYPE_PD,
                    'billable_item_id' => $item->billable_item_id,
                ));
            }

            $resourcePods = ResourcePods::firstOrNew(array(
                'pod_id' => $pod->id,
                'resource_id' => $model->id,
            ));
            $model->resourcePods()->save($resourcePods);
        }
    }

    /**
     * @param Pod $pod
     * @param $userId
     */
    private function addIP(Pod $pod, $userId)
    {
        $publicIP = $pod->getPublicIP();

        if (!$publicIP) {
            return;
        }

        $model = Resources::byName($publicIP, $userId)->first();
        if (!$model) {
            $item = Items::withPod($pod->id);
            $model = Resources::create(array(
                'user_id' => $userId,
                'name' => $publicIP,
                'type' => self::TYPE_IP,
                'billable_item_id' => $item->billable_item_id,
            ));
        }

        $resourcePods = ResourcePods::firstOrNew(array(
            'pod_id' => $pod->id,
            'resource_id' => $model->id,
        ));
        $model->resourcePods()->save($resourcePods);
    }

    /**
     * @param string $description
     * @return null
     */
    private function getNameFromDescription($description)
    {
        if (preg_match('/.* - (.*)/', $description, $match)) {
            return $match[1];
        }

        return null;
    }
}