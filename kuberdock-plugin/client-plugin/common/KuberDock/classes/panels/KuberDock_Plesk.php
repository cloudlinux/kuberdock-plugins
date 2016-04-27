<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\panels\fileManager\Plesk_FileManager;
use Kuberdock\classes\Tools;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\panels\assets\Plesk_Assets;

class KuberDock_Plesk extends KuberDock_Panel
{
    /**
     * @throws CException
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Plesk_FileManager
     */
    public function getFileManager()
    {
        if (!$this->fileManager) {
            $this->fileManager = new Plesk_FileManager();
        }

        return $this->fileManager;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        $session = new \pm_Session();
        $client = $session->getClient();
        return $client->getProperty('login');
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
        $session = new \pm_Session();
        $domain = $session->getCurrentDomain();
        return $domain->getName();
    }

    /**
     * @return string
     */
    public function getHomeDir()
    {
        $data = posix_getpwnam($this->getUser());

        if (isset($data['dir'])) {
            return $data['dir'];
        } else {
            $dir = \pm_Context::getVarDir() . $this->getUser();
            if (!is_dir($dir)) {
                $this->getFileManager($dir, 0700);
            }

            return $dir;
        }
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return 'index';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'api';
    }

    /**
     * @return Plesk_Assets
     */
    public function getAssets()
    {
        if (!$this->assets) {
            $this->assets = Plesk_Assets::model();
        }

        return $this->assets;
    }

    /**
     * @return array
     */
    protected function getAdminData()
    {
        $data = array();
        $fileManager = new \pm_ServerFileManager();
        $content = $fileManager->fileGetContents('/root/.kubecli.conf');

        foreach (explode("\n", $content) as $line) {
            if (in_array(substr($line, 0, 1), array('#', '/'))) continue;

            if (preg_match('/^(.*)=(.*)$/', $line, $match)) {
                $data[trim($match[1])] = trim($match[2]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getUserDomains()
    {
        return $this->kdCommon->getUserDomains($this->getUser());
    }

    /**
     * @return mixed
     */
    public function getUserMainDomain()
    {
        list($domain, $directory) = $this->kdCommon->getUserMainDomain($this->getUser());
        return $domain;
    }
}