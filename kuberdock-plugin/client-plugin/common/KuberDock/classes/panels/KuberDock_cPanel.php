<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\panels\assets\cPanel_Assets;
use Kuberdock\classes\panels\fileManager\cPanel_FileManager;
use Kuberdock\classes\Tools;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;


class KuberDock_cPanel extends KuberDock_Panel
{
    /**
     * @throws CException
     */
    public function __construct()
    {
        $this->user = Base::model()->getStaticPanel()->getUser();
        $this->domain = Base::model()->getStaticPanel()->getDomain();

        $this->api = new \Kuberdock\classes\components\KuberDock_Api();
        $this->api->initUser();

        $this->adminApi = new KuberDock_Api();
        $adminData = $this->getAdminData();
        $username = isset($adminData['user']) ? $adminData['user'] : '';
        $password = isset($adminData['password']) ? $adminData['password'] : '';
        $token = isset($adminData['token']) ? $adminData['token'] : '';
        $this->adminApi->initAdmin($username, $password, $token);

        $data = $this->adminApi->getInfo($this->user, $this->domain);
        $this->billing = $this->getBilling($data);

        if ($service = $this->billing->getService()) {
            $token = isset($service['token']) ? $service['token'] : '';
        } else {
            $token = '';
        }

        $this->command = new KcliCommand('', '', $token);
        $this->kdCommon = new KDCommonCommand();
    }

    /**
     * @return cPanel_FileManager
     */
    public function getFileManager()
    {
        if (!$this->fileManager) {
            $this->fileManager = new cPanel_FileManager();
        }

        return $this->fileManager;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $_ENV['REMOTE_USER'];
    }

    /**
     * @return string
     */
    public function getUserGroup()
    {
        $groupInfo = posix_getgrgid(posix_getegid());
        return $groupInfo['name'];
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $_ENV['DOMAIN'];
    }

    public function getHomeDir()
    {
        return getenv('HOME');
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return 'kuberdock.live.php';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'kuberdock.api.live.php';
    }

    /**
     * @return cPanel_Assets
     */
    public function getAssets()
    {
        if (!$this->assets) {
            $this->assets = cPanel_Assets::model();
        }

        return $this->assets;
    }

    /**
     * @param $response
     * @return mixed
     * @throws CException
     */
    private function  getResponseData($response)
    {
        if(!isset($response['cpanelresult']['result'])) {
            throw new CException(sprintf('Undefined response from %s:%s',
                $response['cpanelresult']['module'], $response['cpanelresult']['func']));
        }

        $result = $response['cpanelresult']['result'];

        if($result['errors']) {
            throw new CException(implode("\n", $result['errors']));
        }

        return $result['data'];
    }

    /**
     * @param $response
     * @return mixed
     * @throws CException
     */
    private function parseModuleResponse($response)
    {
        $data = $this->getResponseData($response);
        $json = json_decode($data, true);

        if(isset($json['status']) && $json['status'] == 'ERROR') {
            throw new CException(sprintf('%s', $json['message']));
        }

        return $json;
    }

    /**
     * @param string $package
     * @return mixed
     * @throws CException
     */
    public function createUser($package)
    {
        $password = Tools::generatePassword();

        $data = array(
            'username' => $this->user,
            'password' => $password,
            'active' => true,
            'rolename' => 'User',
            'package' => $package,
            'email' => $this->user . '@' . $this->domain,
        );

        $data = Base::model()->nativePanel->uapi('KuberDock', 'createUser', array('data' => json_encode($data)));
        $data = $this->parseModuleResponse($data);

        $token = $this->api->getUserToken($this->user, $password);

        $this->command = new KcliCommand('', '', $token);
        $this->command->setConfig();

        return $data;
    }

    public function updatePod($attributes)
    {
        $data = Base::model()->nativePanel->uapi('KuberDock', 'updatePod', array('data' => json_encode($attributes)));
        return $this->parseModuleResponse($data);
    }

    protected function getAdminData()
    {
        $data = Base::model()->nativePanel->uapi('KuberDock', 'getAdminData');
        return $this->parseModuleResponse($data);
    }
}