<?php


namespace models\addon;


use models\Model;

class ResourceItems extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'KuberDock_resource_items';
    /**
     * @var array
     */
    protected $fillable = ['resource_pod_id', 'item_id'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('resource_pod_id', false, true);
            $table->integer('item_id', false, true);

            $table->foreign('resource_pod_id')->references('id')->on(ResourcePods::tableName())->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on(Item::tableName())->onDelete('cascade');
        };
    }

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