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
} 