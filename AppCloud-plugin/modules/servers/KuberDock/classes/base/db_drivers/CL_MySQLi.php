<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 *
 * Not used
 */

namespace base\db_drivers;

use \base\interfaces\CL_iDBDriver;
use \exceptions\CException;

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

    /**
     * @return int
     */
    public function getLastId()
    {

    }
} 