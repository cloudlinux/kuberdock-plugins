<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_User extends CL_User {
    /**
     *
     */
    const ROLE_TRIAL = 'TrialUser';
    /**
     *
     */
    const ROLE_ADMIN = 'Administrator';
    /**
     *
     */
    const ROLE_SUPER_ADMIN = 'SuperAdmin';
    /**
     *
     */
    const ROLE_USER = 'User';

    /**
     * @return bool
     */
    public function isClient()
    {
        return isset($_SESSION['uid']);
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return KuberDock_User
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