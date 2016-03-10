<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class Base {
    /**
     * @var KuberDock_CPanel
     */
    protected $panel;
    /**
     * @var Native hosting panel class
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
     * @return KuberDock_CPanel
     */
    public function getPanel()
    {
        // TODO: difference panels
        if(!$this->panel) {
            $this->panel = new KuberDock_CPanel();
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