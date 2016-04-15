<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

use \base\db_drivers\CL_MySQL;
use \base\db_drivers\CL_MySQLi;
use \base\db_drivers\CL_PDO;
use \base\interfaces\CL_iDBDriver;

class CL_Query extends CL_Base implements CL_iDBDriver
{
    /**
     * @object CL_MySQL | CL_MySQLi
     */
    private $driver;

    /**
     *
     */
    public function __construct()
    {
        $this->driver = new CL_PDO();
    }

    /**
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public function query($query, $params = array())
    {
        return $this->driver->query($query, $params);
    }

    /**
     * @return mixed
     */
    public function getRow()
    {
        return $this->driver->getRow();
    }

    /**
     * @return mixed
     */
    public function getRows()
    {
        return $this->driver->getRows();
    }

    /**
     * @return int
     */
    public function getLastId()
    {
        return $this->driver->getLastId();
    }
} 