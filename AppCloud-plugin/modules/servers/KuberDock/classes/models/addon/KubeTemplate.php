<?php


namespace models\addon;


use models\Model;

class KubeTemplate extends Model
{
    /**
     * Can be deleted by admin
     */
    const TYPE_STANDARD = 0;

    /**
     * Cannot be deleted by admin
     */
    const TYPE_NON_STANDARD = 1;
    /**
     *
     */
    const STANDARD_KUBE_IDS = [0, 1, 2];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_kubes_templates';
    /**
     * @var array
     */
    protected $fillable = [
        'kuber_kube_id', 'kube_name', 'kube_type', 'cpu_limit',
        'memory_limit', 'hdd_limit', 'traffic_limit','server_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function relatedKubes()
    {
        return $this->hasMany('models\addon\KubeRelation', 'template_id');
    }
}