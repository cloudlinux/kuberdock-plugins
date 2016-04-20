<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\KuberDock_Assets;
use Kuberdock\classes\Tools;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;
use Kuberdock\classes\panels\billing\WHMCS;
use Kuberdock\classes\panels\billing\BillingInterface;
use Kuberdock\classes\panels\billing\NoBilling;
use Kuberdock\classes\panels\assets\KuberDock_Plesk_Assets;

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
        $data = $this->api->getInfo($this->user, $this->domain);
        $this->billing = $this->getBilling($data);

        if($service = $this->billing->getService()) {
            $username = isset($service['username']) ? $service['username'] : '';
            $password = isset($service['password']) ? $service['password'] : '';
            $token = isset($service['token']) ? $service['token'] : '';
        } else {
            $username = KcliCommand::DEFAULT_USER;
            $password = KcliCommand::DEFAULT_USER;
            $token = '';
        }

        if($token) {
            $this->api->setToken($token);
        }

        $this->command = new KcliCommand($username, $password, $token);
        $this->kdCommon = new KDCommonCommand();
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
        $dir = \pm_Context::getVarDir() . $this->getUser();

        if (!is_dir($dir)) {
            mkdir($dir, 0700);
        }

        return $dir;
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return '';
    }

    /**
     * @return KuberDock_Plesk_Assets
     */
    public function getAssets()
    {
        if (!$this->assets) {
            $this->assets = KuberDock_Plesk_Assets::model();
        }

        return $this->assets;
    }

    public function setAssets(KuberDock_Assets $assets)
    {
        $this->assets = $assets;
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
}