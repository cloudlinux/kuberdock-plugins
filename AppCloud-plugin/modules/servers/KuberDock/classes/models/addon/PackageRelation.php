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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kubes()
    {
        return $this->hasMany('models\addon\KubePrice', 'product_id', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id', 'id');
    }
}