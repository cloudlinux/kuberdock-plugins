<?php


namespace models\addon;


use models\billing\Package;
use models\Model;

class State extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'KuberDock_states';
    /**
     * @var array
     */
    protected $fillable = [];
    /**
     * @var array
     */
    protected $dates = ['checkin_date'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('hosting_id');
            $table->integer('product_id');
            $table->date('checkin_date');
            $table->integer('kube_count');
            $table->integer('ps_size');
            $table->integer('ip_count');
            $table->float('total_sum');
            $table->text('details');

            $table->foreign('product_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        };
    }

    /**
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
    }

    /**
     * @param string $value
     * @return array
     */
    public function getDetailsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param string $value
     */
    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = json_encode($value);
    }
}