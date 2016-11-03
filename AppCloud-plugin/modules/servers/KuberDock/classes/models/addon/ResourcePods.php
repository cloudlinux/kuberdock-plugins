<?php


namespace models\addon;


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
     * @var array
     */
    protected $fillable = ['pod_id', 'resource_id', 'item_id'];

    /**
     * @return Item
     */
    public function item()
    {
        return $this->belongsTo('models\addon\Item', 'item_id');
    }

    /**
     * @return Resources
     */
    public function resources()
    {
        return $this->belongsTo('models\addon\Resources', 'resource_id');
    }
}