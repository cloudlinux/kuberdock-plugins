<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Server extends CL_Server {
    /**
     * @var array
     */
    public static $_api = array();

    /**
     * @return string
     */
    public function getLoginPageLink()
    {
        return $this->getApiServerUrl();
    }

    /**
     * @param int $groupId
     * @return KuberDock_Api
     */
    public function getApiByGroup($groupId)
    {
        return KuberDock_ServerGroup::model()->loadById($groupId)->getApi();
    }

    /**
     * @param null|int $serverId
     * @return KuberDock_Api
     * @throws Exception
     */
    public function getApi($serverId = null)
    {
        $serverId = $serverId ? $serverId : $this->id;

        if(!$serverId) {
            throw new Exception('Cannot get api');
        }

        if(isset(self::$_api[$serverId])) {
            return self::$_api[$serverId];
        } else {
            if(!isset($this->id)) {
                $this->setAttributes($this->loadById($serverId));
            }

            $url = $this->getApiServerUrl();
            $password = $this->password;
            $password = CL_Hosting::model()->decryptPassword($password);
            self::$_api[$serverId] = new KuberDock_Api($this->username, $password, $url);
            if($this->accesshash && $this->username) {
                self::$_api[$serverId]->setToken($this->accesshash);
            }

            return self::$_api[$serverId];
        }
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function getActive()
    {
        $rows = $this->loadByAttributes(array(
            'type' => KUBERDOCK_MODULE_NAME,
            'disabled' => 0,
            'active' => 1,
        ));

        if(!$rows) {
            throw new Exception('There is no active server');
        }

        $this->setAttributes(current($rows));

        return $this;
    }

    /**
     * @return array
     */
    public function getServers()
    {
        $data = array();
        $rows = $this->loadByAttributes(array(
            'type' => KUBERDOCK_MODULE_NAME,
            'disabled' => 0,
        ));

        foreach($rows as $row) {
            $server = new $this;
            $data[$row['id']] = $server->loadByParams($row);
        }

        return $data;
    }

    /**
     * @param string $username
     * @param string $password
     * @param null|int $serverId
     * @return KuberDock_Api
     */
    public function getApiByUser($username, $password, $serverId = null)
    {
        $api = $this->getApi($serverId);
        $api->setToken('');

        return $api->setLogin($username, $password);
    }

    /**
     * @param string $token
     * @param null|int $serverId
     * @return KuberDock_Api
     */
    public function getApiByToken($token, $serverId = null)
    {
        $api = $this->getApi($serverId);
        $api->setLogin('', '');
        return $api->setToken($token);
    }

    /**
     * @return string
     */
    public function getApiServerUrl()
    {
        $scheme = $this->secure == 'on' ? KuberDock_Api::PROTOCOL_HTTPS : KuberDock_Api::PROTOCOL_HTTP;
        $url = sprintf('%s://%s', $scheme, $this->ipaddress);

        return $url;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getGroupId()
    {
        $sql = 'SELECT g.id FROM `tblservergroups` g
          LEFT JOIN `tblservergroupsrel` r ON g.id=r.groupid
          LEFT JOIN `tblservers` s ON r.serverid=s.id
            WHERE s.disabled = 0 AND s.id = ? ORDER BY g.id DESC';

        $server = CL_Query::model()->query($sql, array(
            $this->id,
        ))->getRow();

        if(!$server) {
            throw new Exception('Group not founded');
        }

        return $server['id'];
    }

    /**
     * @return bool
     */
    public function isKuberDock()
    {
        return $this->type == KUBERDOCK_MODULE_NAME;
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