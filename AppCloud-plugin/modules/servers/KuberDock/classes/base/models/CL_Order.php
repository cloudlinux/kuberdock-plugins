<?php
/**
 * @project whmcs-plugin
 */

namespace base\models;

use base\CL_Model;

class CL_Order extends CL_Model {

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblorders';
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(!isset(self::$_models[$className])) {
            self::$_models[$className] = new $className;
        }

        return self::$_models[$className];
    }
} 