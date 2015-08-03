<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */


class CL_MySQL implements CL_iDBDriver {
    /**
     * @var
     */
    private $result;

    /**
     * @param string $query
     * @param array $values
     * @return $this | int
     * @throws Exception
     */
    public function query($query, $values = array())
    {
        $key = $format = array();

        foreach($values as &$value) {
            $value = mysql_real_escape_string($value);
            $key[] = self::MYSQL_REPLACE_KEY;
            $format[] = '"%s"';
        }

        $sql = vsprintf(str_replace($key, $format ,$query), $values);

        if(!$this->result = mysql_query($sql)) {
            throw new Exception('Query error: ' . mysql_error());
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