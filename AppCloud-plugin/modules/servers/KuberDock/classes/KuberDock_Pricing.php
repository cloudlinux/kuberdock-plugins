<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Pricing extends CL_Model {
    const TYPE_PRODUCT = 'product';
    const TYPE_ADDON = 'addon';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblpricing';
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