<?php


namespace models\addon;


use models\Model;

class KubeRelation extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_kubes_links';
    /**
     * @var array
     */
    protected $fillable = ['product_id', 'kuber_product_id', 'kube_price'];
}