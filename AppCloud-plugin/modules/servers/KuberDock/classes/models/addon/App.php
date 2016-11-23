<?php


namespace models\addon;


use exceptions\CException;
use models\addon\resource\PredefinedApp;
use models\addon\resource\ResourceFactory;
use models\billing\Package;
use models\Model;

class App extends Model
{
    /**
     *
     */
    const SESSION_FIELD = 'KuberDock_app_id';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_preapps';

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('product_id');
            $table->integer('kuber_product_id');
            $table->string('pod_id', 64);
            $table->text('data');
            $table->enum('type', [
                ResourceFactory::TYPE_POD,
                ResourceFactory::TYPE_YAML,
            ])->default(ResourceFactory::TYPE_POD);
            $table->text('referer');

            $table->index('pod_id');
            $table->index('user_id');

            $table->foreign('product_id')->references('product_id')->on('KuberDock_products')->onDelete('cascade');
        };
    }

    /**
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeNotCreated($query)
    {
        return $query->whereNull('pod_id');
    }

    /**
     *
     */
    public function addToSession()
    {
        $_SESSION[self::SESSION_FIELD] = $this->id;
    }

    /**
     * @return $this|null
     */
    public static function getFromSession()
    {
        return isset($_SESSION[self::SESSION_FIELD]) ? App::find($_SESSION[self::SESSION_FIELD]) : null;
    }

    /**
     *
     */
    public function deleteFromSession()
    {
        if (isset($_SESSION[self::SESSION_FIELD])) {
            $app = App::find($_SESSION[self::SESSION_FIELD]);
            if ($app) {
                $app->delete();
            }

            unset($_SESSION[self::SESSION_FIELD]);
        }
    }

    /**
     * @return ResourceFactory
     * @throws CException
     */
    public function getResource()
    {
        if ($this->type == ResourceFactory::TYPE_YAML) {
            $resource = new PredefinedApp($this->package);
        } else if ($this->type == ResourceFactory::TYPE_POD) {
            $resource = '';
        } else {
            throw new CException('Unknown resource type');
        }

        return $resource->load($this->data);
    }
}