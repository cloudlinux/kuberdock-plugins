<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;

class KuberDock_Addon_Trial extends CL_Model {
    /**
     *
     */
    public function init()
    {
        $this->_pk = 'user_id';
    }

    public function setTableName()
    {
        $this->tableName = 'KuberDock_trial';
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