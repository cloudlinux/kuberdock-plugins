<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use base\CL_Model;

class CL_Configuration extends CL_Model {
    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblconfiguration';
    }

    /**
     * @return $this
     */
    public function get()
    {
        if(!$this->getAttributes()) {
            $values = $this->loadByAttributes();

            foreach($values as $value) {
                $this->setAttribute($value['setting'], $value['value']);
            }
        }

        return $this;
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