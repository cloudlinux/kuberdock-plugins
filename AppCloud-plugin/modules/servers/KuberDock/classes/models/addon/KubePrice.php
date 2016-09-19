<?php


namespace models\addon;


use models\billing\Admin;
use models\Model;

class KubePrice extends Model
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
    protected $fillable = ['template_id', 'product_id', 'kuber_product_id', 'kube_price'];
    /**
     * @var array
     */
    protected $dates = ['change_time'];

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($kubePrice) {
            $kubePrice->addKubeToPackage();
            $kubePrice->log();

        });

        static::deleted(function ($kubePrice) {
            $kubePrice->removeKubeFromPackage();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo('models\addon\KubeTemplate', 'template_id');
    }

    /**
     *
     */
    public function addKubeToPackage()
    {
        $this->template->server->getApi()
            ->addKubeToPackage($this->kuber_product_id, $this->template->kuber_kube_id, $this->kube_price);
    }

    /**
     *
     */
    public function removeKubeFromPackage()
    {
        $this->template->server->getApi()
            ->deletePackageKube($this->kuber_product_id, $this->template->kuber_kube_id);
    }

    /**
     *
     */
    public function log()
    {
        $priceChange = new KubePriceChange();
        $priceChange->setRawAttributes([
            'login' => Admin::getCurrent()->username,
            'change_time' => new \DateTime(),
            'type_id' => $this->template->kuber_kube_id,
            'package_id' => $this->kuber_product_id,
            'old_value' => $this->getOriginal('kube_price'),
            'new_value' => $this->kube_price,
        ]);
        $priceChange->save();
    }
}