<?php


namespace models\billing;


use models\Model;

class PackageGroup extends Model
{
    const DEFAULT_NAME = 'KuberDock';

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblproductgroups';

    protected $fillable = array('name');
}