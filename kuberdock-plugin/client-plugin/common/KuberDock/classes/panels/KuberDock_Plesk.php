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
        $this->user = Base::model()->getStaticPanel()->getUser();
        $this->domain = Base::model()->getStaticPanel()->getDomain();

        $this->api = new KuberDock_Api();
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
     * @param string $package
     * @return mixed
     * @throws CException
     */
    public function createUser($package)
    {
        return;
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

        return $data;
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