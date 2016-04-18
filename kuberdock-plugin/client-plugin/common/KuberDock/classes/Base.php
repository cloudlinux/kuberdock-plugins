<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

namespace Kuberdock\classes;

use Kuberdock\classes\panels\KuberDock_cPanel;

class Base {
    /**
     * @var KuberDock_cPanel
     */
    protected $panel;
    /**
     * @var object Native hosting panel class
     */
    public $nativePanel;
    /**
     * @var array
     */
    protected static $_models;

    /**
     * @param $object
     */
    public function setPanel($object)
    {
        $this->panel = $object;
    }

    /**
     * @return KuberDock_cPanel
     */
    public function getPanel()
    {
        // TODO: difference panels
        if(!$this->panel) {
            $this->panel = new KuberDock_cPanel();
        }

        return $this->panel;
    }

    /**
     * @param object $panel
     */
    public function setNativePanel($panel)
    {
        $this->nativePanel =$panel;
    }

    /**
     * @param $panel
     * @return object
     */
    public function getNativePanel($panel)
    {
        return $this->nativePanel;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 