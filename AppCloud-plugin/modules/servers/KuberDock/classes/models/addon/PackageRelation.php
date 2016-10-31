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

    /**
     * @param $query
     * @param string $referer
     * @return mixed
     */
    public function scopeByReferer($query, $referer)
    {
        return $query->select('KuberDock_products.*')
            ->join('tblproducts', 'tblproducts.id', '=', 'KuberDock_products.product_id')
            ->join('tblservergroups', 'tblservergroups.id', '=', 'tblproducts.servergroup')
            ->join('tblservergroupsrel', 'tblservergroupsrel.groupid', '=', 'tblservergroups.id')
            ->join('tblservers', 'tblservers.id', '=', 'tblservergroupsrel.serverid')
            ->where('tblproducts.hidden', '!=', 1)
            ->where(function ($query) use ($referer) {
                $query->whereRaw('INSTR(?, tblservers.ipaddress) > 0', [$referer])
                    ->orWhereRaw('INSTR(?, tblservers.hostname) > 0', [$referer]);
            });
    }
}