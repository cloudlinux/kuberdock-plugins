<?php


namespace models\addon;


use models\billing\Server;
use models\Model;

class KubeTemplate extends Model
{
    /**
     * Can't be deleted by admin
     */
    const TYPE_STANDARD = 0;

    /**
     * Can be deleted by admin
     */
    const TYPE_NON_STANDARD = 1;

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
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function($template) {
            $template->createKube();
        });

        static::deleted(function($template) {
            $template->deleteKube();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kubePrice()
    {
        return $this->hasMany('models\addon\KubePrice', 'template_id');
    }

    /**
     * @return Server
     */
    public function server()
    {
        return $this->belongsTo('models\billing\Server', 'server_id');
    }

    /**
     * @return mixed
     */
    public function getDefaultTemplate()
    {
        $defaultKube = $this->server()->getApi()->getDefaultKubeType()->getData();

        return KubeTemplate::where('kuber_kube_id', $defaultKube['id'])->first();
    }

    /**
     * @return bool
     */
    public function isDeletable()
    {
        return $this->kube_type == self::TYPE_NON_STANDARD && !$this->kubePrice->count();
    }

    /**
     *
     */
    public function createKube()
    {
        if (!is_null($this->kuber_kube_id)) {
            return;
        }

        $data = $this->server->getApi()->createKube([
            'name' => $this->kube_name,
            'cpu' => $this->cpu_limit,
            'cpu_units' => 'Cores',
            'disk_space' => $this->hdd_limit,
            'memory' => $this->memory_limit,
            'memory_units' => 'MB',
            'included_traffic' => 0,
        ])->getData();

        $this->kuber_kube_id = $data['id'];
    }

    /**
     *
     */
    public function deleteKube()
    {
        $this->server->getApi()->deleteKube($this->kuber_kube_id);
    }
}