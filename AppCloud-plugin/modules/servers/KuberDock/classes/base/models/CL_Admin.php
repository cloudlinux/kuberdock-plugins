<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Model;

class CL_Admin extends CL_Model {
    /**
     *
     */
    const FULL_ADMINISTRATOR_ROLE_ID = 1;

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tbladmins';
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDefault()
    {
        $admin = CL_Admin::model()->loadByAttributes(array(
            'roleid' => self::FULL_ADMINISTRATOR_ROLE_ID,
            'disabled' => 0,
        ), '', array('limit' => 1));

        if(!$admin) {
            throw new Exception('Cannot get admin user.');
        }

        return current($admin);
    }

    public static function getCurrentAdmin()
    {
        return current(self::model()->loadByAttributes(array('id' => $_SESSION['adminid'])));
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