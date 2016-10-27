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
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
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
    public function getFromSession()
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