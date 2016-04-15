<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class Base {
    /**
     * @var KuberDock_cPanel
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

    public function unsetPanel()
    {
        unset($this->panel);
    }

    /**
     * @return KuberDock_cPanel
     */
    public function getPanel()
    {
        // TODO: different panels
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