<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Model;
use models\billing\Admin;

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
     * @return Admin
     */
    public function getCurrentAdmin()
    {
        return Admin::getCurrent();
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function getCurrent()
    {
        return $this->loadById($this->getCurrentUserId());
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getCurrentUserId()
    {
        if(!isset($_SESSION['uid'])) {
            throw new Exception('User not authorized');
        }

        return  $_SESSION['uid'];
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public function getClientDetails($userId)
    {
        $admin = Admin::getCurrent();
        $values["clientid"] = $userId;
        $values["stats"] = true;

        $results = localAPI('getclientsdetails', $values, $admin->username);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getGateway()
    {
        if($this->defaultgateway) {
            return $this->defaultgateway;
        } else {
            $gateways = CL_Currency::model()->getPaymentGateways();
            return current($gateways)['module'];
        }
    }
} 