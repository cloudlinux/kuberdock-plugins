<?php


namespace models\addon;


use models\Model;

class PackageRelation extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $primaryKey = 'product_id';
    /**
     * @var string
     */
    protected $table = 'KuberDock_products';

    /**
     * @var array
     */
    protected $fillable = ['kuber_product_id'];
}