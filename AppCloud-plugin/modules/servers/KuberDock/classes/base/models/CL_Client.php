<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

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
     * Class loader
     *
     * @param string $className
     * @return CL_Client
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