<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use Exception;
use base\CL_Model;
use base\CL_Query;

class KuberDock_ServerGroup extends CL_Model
{
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
            throw new Exception('Active server not found');
        }

        return KuberDock_Server::model()->loadByParams($server);
    }
} 