<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;

class KuberDock_Pricing extends CL_Model
{
    const TYPE_PRODUCT = 'product';
    const TYPE_ADDON = 'addon';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblpricing';
    }
} 