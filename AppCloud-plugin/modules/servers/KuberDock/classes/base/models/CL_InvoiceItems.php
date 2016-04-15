<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use base\CL_Model;

class CL_InvoiceItems extends CL_Model
{
    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblinvoiceitems';
    }
} 