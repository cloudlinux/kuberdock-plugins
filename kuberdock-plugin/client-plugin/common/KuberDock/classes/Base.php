<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

namespace Kuberdock\classes;

use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\panels\KuberDock_Panel;

class Base {
    /**
     * @var KuberDock_Panel
     */
    protected $panel;
    /**
     * @var KuberDock_Panel
     */
    protected $staticPanel;
    /**
     * @var object Native hosting panel class
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
     * @return KuberDock_Panel
     * @throws CException
     */
    public function getPanel()
    {
        $kdCommon = new KDCommonCommand();
        $panel = $kdCommon->getPanel();

        try {
            if (!$this->panel) {
                $this->panel = new \ReflectionClass('Kuberdock\classes\panels\KuberDock_' . $panel);
                $this->panel = $this->panel->newInstance();
            }

            if (!$this->staticPanel) {
                $this->staticPanel = $this->panel->newInstanceWithoutConstructor();
            }

            return $this->panel;
        } catch (\ReflectionException $e) {
            throw new CException('Unknown panel');
        }
    }

    /**
     * @return KuberDock_Panel
     * @throws CException
     */
    public function getStaticPanel()
    {
        $kdCommon = new KDCommonCommand();
        $panel = $kdCommon->getPanel();

        try {
            $obj = new \ReflectionClass('Kuberdock\classes\panels\KuberDock_' . $panel);
            $this->staticPanel = $obj->newInstanceWithoutConstructor();
            return $this->staticPanel;
        } catch (\ReflectionException $e) {
            throw new CException('Unknown panel');
        }
    }

    /**
     * @param object $panel
     */
    public function setNativePanel($panel)
    {
        $this->nativePanel = $panel;
    }

    /**
     * @return object
     */
    public function getNativePanel()
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