<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\db_drivers;

use \base\interfaces\CL_iDBDriver;

class CL_MySQLi implements CL_iDBDriver {

    public function query($query, $values = array())
    {
        // future
    }

    public function getRow()
    {
        // future
    }

    public function getRows()
    {
        // future
    }
} 