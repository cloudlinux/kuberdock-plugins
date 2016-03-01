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