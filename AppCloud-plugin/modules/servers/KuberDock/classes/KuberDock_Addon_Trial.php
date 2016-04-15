<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;

class KuberDock_Addon_Trial extends CL_Model {
    /**
     *
     */
    public function init()
    {
        $this->_pk = 'user_id';
    }

    public function setTableName()
    {
        $this->tableName = 'KuberDock_trial';
    }
} 