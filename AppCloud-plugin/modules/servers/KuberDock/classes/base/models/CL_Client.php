<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use exceptions\CException;
use base\CL_Model;

class CL_Client extends CL_Model {

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblclients';
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getClientsByApi($attributes = array())
    {
        $admin = CL_User::model()->getCurrentAdmin();

        $results = localAPI('getclients', $attributes, $admin['username']);

        return ($results['result'] == 'success') ? $results['clients']['client'] : array();
    }

    /**
     * @param $userId
     * @return array
     */
    public function getClientDetails($userId)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $adminuser = $admin['username'];
        $values['clientid'] = $userId;
        $values['stats'] = true;

        $results = localAPI('getclientsdetails', $values, $adminuser);

        return ($results['result'] == 'success') ? $results : array();
    }

    /**
     * @param string $username
     * @param array $userDomains
     * @return array
     * @throws CException
     */
    public function getClientByCpanelUser($username, $userDomains)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['username2'] = $username;
        $results = localAPI('getclientsproducts', $values, $admin['username']);

        if($results['result'] == 'error') {
            throw new CException($results['message']);
        }

        foreach($results['products']['product'] as $row) {
            if(in_array($row['domain'], $userDomains)) {
                $clientId = $row['clientid'];
                break;
            }
        }

        if(!isset($clientId)) {
            throw new CException('User has no cPanel service in WHMCS. Cannot find user by login');
        }

        $details = $this->getClientDetails($clientId);

        if($details['result'] != 'success') {
            throw new CException('User ' . $username . ' not founded');
        }

        return $details['client'];
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