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
        parent::__construct();
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

    protected function getAdminData()
    {
        $data = Base::model()->nativePanel->uapi('KuberDock', 'getAdminData');
        return $this->parseModuleResponse($data);
    }
}