<?php


namespace models\addon;


use models\billing\Currency;
use models\Model;

class KubePriceChange extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_price_changes';

    /**
     * @var
     */
    protected $currency;

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('login');
            $table->dateTime('change_time');
            $table->integer('type_id');
            $table->integer('package_id');
            $table->float('old_value')->nullable();
            $table->float('new_value')->nullable();

            $table->index('new_value');

            $table->foreign('package_id')->references('kuber_product_id')->on('KuberDock_products')->onDelete('cascade');
        };
    }

    /**
     *
     */
    public function bootIfNotBooted()
    {
        parent::bootIfNotBooted();

        $this->currency = Currency::getDefault();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kubeTemplate()
    {
        return $this->belongsTo('models\addon\KubeTemplate', 'type_id', 'kuber_kube_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function packageRelation()
    {
        return $this->belongsTo('models\addon\PackageRelation', 'package_id', 'kuber_product_id');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        $kubeName = $this->kubeTemplate ? $this->kubeTemplate->kube_name : $this->type_id;
        $packageName = $this->packageRelation->package ? $this->packageRelation->package->name : $this->package_id;

        if (is_null($this->old_value)) {
            return 'Kube type #' . $kubeName . ' added to package #' . $packageName
            . ' with price ' . $this->currency->getFullPrice($this->new_value);
        }

        if (is_null($this->new_value)) {
            return 'Kube type #' . $kubeName . ' with price ' . $this->currency->getFullPrice($this->old_value)
            . ' removed from package #' . $packageName;
        }

        return 'Kube type #' . $kubeName . ' price for package #' . $packageName
        . ' changed from ' . $this->currency->getFullPrice($this->old_value)
        . ' to ' . $this->currency->getFullPrice($this->new_value);
    }
}