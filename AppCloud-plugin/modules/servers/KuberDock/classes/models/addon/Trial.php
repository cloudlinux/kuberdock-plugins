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
}