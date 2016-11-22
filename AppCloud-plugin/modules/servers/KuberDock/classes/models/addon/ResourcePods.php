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
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->string('pod_id', 64)->nullable();
            $table->integer('resource_id', false, true);
            $table->integer('item_id', false, true);

            $table->index('pod_id');
            $table->index('resource_id');

            $table->foreign('resource_id')->references('id')->on(Resources::tableName())->onDelete('cascade');
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