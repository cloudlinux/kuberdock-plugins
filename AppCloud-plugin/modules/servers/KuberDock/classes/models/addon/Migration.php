<?php


namespace models\addon;


use models\Model;

class Migration extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_migrations';
    /**
     * @var array
     */
    protected $fillable = ['version'];
}