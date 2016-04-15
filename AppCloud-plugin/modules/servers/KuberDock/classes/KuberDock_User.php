<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\models\CL_User;

class KuberDock_User extends CL_User
{
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
     *
     */
    const ROLE_RESTRICTED_USER = 'LimitedUser';

    /**
     * @return bool
     */
    public function isClient()
    {
        return isset($_SESSION['uid']);
    }
} 