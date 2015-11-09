<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Model;

class CL_User extends CL_Model {
    const STATUS_ACTIVE = 'Active';
    const STATUS_PENDING = 'Pending';
    const STATUS_SUSPENDED = 'Suspended';
    const STATUS_TERMINATED = 'Terminated';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_FRAUD = 'Fraud';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblclients';
    }

    /**
     * @return array
     */
    public function getCurrentAdmin()
    {
        return CL_Admin::model()->getDefault();
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public function getClientDetails($userId)
    {
        $admin = $this->getCurrentAdmin();
        $values["clientid"] = $userId;
        $values["stats"] = true;

        $results = localAPI('getclientsdetails', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
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