<?php


namespace models\addon;


use components\KuberDock_InvoiceItem;
use components\Pod;
use components\Units;
use models\Model;

class ResourcePods extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_resource_pods';
    /**
     * @var string
     */
    protected $primaryKey = 'pod_id';
    /**
     * @var array
     */
    protected $fillable = ['pod_id', 'resource_id'];

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($resourcePod) {
            /* @var self $resourcePod*/
            $resourcePod->divide();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resources()
    {
        return $this->belongsTo('models\addon\Resources', 'resource_id');
    }

    /**
     * Added new pod with existing resource (probably via pod edit), divide it
     */
    public function divide()
    {
        /**
         * @var Resources $resource
         * @var Items $items
         * @var Pod $pod
         */
        $moreResources = ResourcePods::where('resource_id', $this->resource_id)
            ->where('pod_id', '!=', $this->pod_id)->first();
        $resource = $moreResources->resources;

        if ($moreResources && $resource->isActive()) {
            $items = Items::where('billable_item_id', $resource->billable_item_id)->first();
            $pod = $items->service->getPod()->load($this->pod_id);

            if ($resource->type == Resources::TYPE_PD) {
                $description = 'Storage: ' . $resource->name;
                $units = Units::getHDDUnits();
                $price = $items->service->package->getPricePersistentStorage();
                $pd = current(array_filter($pod->getPersistentDisk(), function($e) use ($resource) {
                    if ($e['pdName'] == $resource->name) {
                        return $e;
                    }
                }));
                $qty = $pd['pdSize'];
            } else {
                $description = 'IP: ' . $resource->name;
                $units = '';
                $price = $price = $items->service->package->getPriceIP();
                $qty = 1;
            }

            $invoiceItem = KuberDock_InvoiceItem::create($description, $price, $units, $qty, $resource->type);
            $moreResources->resources->divide($invoiceItem);
        }
    }
}