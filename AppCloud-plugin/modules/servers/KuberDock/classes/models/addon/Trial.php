<?php


namespace models\addon;


use models\Model;

class Trial extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_trial';
    /**
     * @var string
     */
    protected $primaryKey = 'service_id';
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'service_id'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('user_id');
            $table->integer('service_id')->unique();
        };
    }
}