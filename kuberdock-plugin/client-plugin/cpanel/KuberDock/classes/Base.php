<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class Base {
    /**
     * @var object
     */
    public $panel;
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