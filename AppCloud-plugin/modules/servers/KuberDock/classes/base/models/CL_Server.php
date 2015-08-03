<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Server extends CL_Model {
    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblservers';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function decryptPassword()
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $this->password;

        $results = localAPI('decryptpassword', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
    }

    /**
     * @param $password
     * @return string
     * @throws Exception
     */
    public function encryptPassword($password)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $password;

        $results = localAPI('encryptpassword', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
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