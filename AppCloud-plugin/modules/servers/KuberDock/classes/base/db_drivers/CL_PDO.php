<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\db_drivers;

use PDO;
use PDOStatement;
use \base\interfaces\CL_iDBDriver;
use \exceptions\CException;

class CL_PDO implements CL_iDBDriver {
    /**
     * @var PDO
     */
    private $pdo;
    /**
     * @var PDOStatement
     */
    private $stmt;

    /**
     *
     */
    public function __construct()
    {
        global $db_host, $db_name, $db_username, $db_password, $mysql_charset;

        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$mysql_charset";
        $opt = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        $this->pdo = new PDO($dsn, $db_username, $db_password, $opt);
    }

    /**
     * @param string $query
     * @param array $values
     * @return $this
     */
    public function query($query, $values = array())
    {
        $this->stmt = $this->pdo->prepare($query);
        $this->stmt->execute($values);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRow()
    {
        return $this->stmt->fetch();
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->stmt->fetchAll();
    }

    /**
     * @return int
     */
    public function getLastId()
    {
        return $this->pdo->lastInsertId();
    }
} 