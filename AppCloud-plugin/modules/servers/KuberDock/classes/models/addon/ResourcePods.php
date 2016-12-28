<?php


namespace models\addon;


use Carbon\Carbon;
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
    protected $fillable = ['pod_id', 'resource_id'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('pod_id', 64);
            $table->integer('resource_id', false, true);

            $table->index('pod_id');
            $table->index('resource_id');

            $table->foreign('resource_id')->references('id')->on(Resources::tableName())->onDelete('cascade');
        };
    }

    /**
     * @return Item
     */
    public function items()
    {
        return $this->belongsToMany('models\addon\Item', ResourceItems::tableName(), 'resource_pod_id', 'item_id');
    }

    /**
     * @return Resources
     */
    public function resources()
    {
        return $this->belongsTo('models\addon\Resources', 'resource_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePaidDeletedItems($query)
    {
        return $query->with('items')
            ->whereHas('items', function ($query) {
                $query->where('status', Resources::STATUS_PAID_DELETED)
                    ->where('due_date', '>=', new Carbon());
            });
    }

}