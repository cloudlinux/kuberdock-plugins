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

class CL_MySQL implements CL_iDBDriver {
    /**
     * @var
     */
    private $result;

    /**
     * @param string $query
     * @param array $values
     * @return $this | int
     * @throws CException
     */
    public function query($query, $values = array())
    {
        foreach($values as &$value) {
            if(stripos($value, 'null') !== false || is_null($value)) {
                $value = 'null';
            } else {
                $value = sprintf("'%s'", mysql_real_escape_string($value));
            }
            $query = preg_replace('/\?/', $value, $query, 1);
        }

        if(!$this->result = mysql_query($query)) {
            throw new CException('Query error: ' . mysql_error());
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRow()
    {
        return mysql_fetch_assoc($this->result);
    }

    /**
     * @return array
     */
    public function getRows()
    {
        $rows = array();

        while($row = mysql_fetch_assoc($this->result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return int
     */
    public function getLastId()
    {
        return mysql_insert_id();
    }
} 