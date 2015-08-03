<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Query extends CL_Base implements CL_iDBDriver {
    /**
     * @object CL_MySQL | CL_MySQLi
     */
    private $driver;

    /**
     *
     */
    public function __construct()
    {
        if(function_exists('mysql_query')) {
            $this->driver = new CL_MySQL;
        } else {
            // future
            $this->driver = new CL_MySQLi;
        }
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
     * Class loader
     *
     * @param string $className
     * @return CL_MySQL | CL_MySQLi
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