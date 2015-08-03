<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_ServerGroup extends CL_Model {
    const FILL_TYPE_ACTIVE = 2;
    const FILL_TYPE_LEAST_FULL = 1;

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblservergroups';
    }

    /**
     * @return KuberDock_Server
     */
    public function getActiveServer()
    {
        switch($this->filltype) {
            case self::FILL_TYPE_LEAST_FULL:
                return $this->getLeastFullServer();
                break;
            default:
                return $this->getDefaultServer();
                break;
        }
    }

    /**
     * @return KuberDock_Server
     * @throws Exception
     */
    public function getLeastFullServer()
    {
        $sql = 'SELECT s.*, count(h.id) AS cnt FROM `tblservers` s
          LEFT JOIN `tblservergroupsrel` r ON s.id=r.serverid
          LEFT JOIN tblhosting h ON s.id=h.server
            WHERE r.groupid = ? AND s.disabled = 0
          GROUP BY s.id HAVING cnt <= s.maxaccounts ORDER BY cnt ASC LIMIT 1';

        $server = CL_Query::model()->query($sql, array(
            $this->id,
        ))->getRow();

        if(!$server) {
            throw new Exception('Server not found');
        }

        return KuberDock_Server::model()->loadByParams($server);
    }

    /**
     * @return KuberDock_Server
     * @throws Exception
     */
    public function getDefaultServer()
    {
        $sql = 'SELECT s.* FROM `tblservers` s
          LEFT JOIN `tblservergroupsrel` r ON s.id=r.serverid
            WHERE r.groupid = ? AND s.active = 1  AND s.disabled = 0
        GROUP BY s.id';

        $server = CL_Query::model()->query($sql, array(
            $this->id,
        ))->getRow();

        if(!$server) {
            throw new Exception('Server not found');
        }

        return KuberDock_Server::model()->loadByParams($server);
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
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