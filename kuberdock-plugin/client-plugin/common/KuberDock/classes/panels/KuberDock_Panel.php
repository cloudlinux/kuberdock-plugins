<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\Base;
use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\api\Response;
use Kuberdock\classes\panels\assets\Assets;
use Kuberdock\classes\panels\fileManager\FileManagerInterface;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;
use Kuberdock\classes\panels\billing\WHMCS;
use Kuberdock\classes\panels\billing\BillingInterface;
use Kuberdock\classes\panels\billing\NoBilling;
use Kuberdock\classes\Tools;

abstract class KuberDock_Panel
{
    /**
     * @var
     */
    public $user;
    /**
     * @var
     */
    public $domain;
    /**
     * @var billing\BillingInterface
     */
    public $billing;

    /**
     * @var KuberDock_ApiResponse
     */
    protected $_data;
    /**
     * @var KcliCommand
     */
    protected $command;
    /**
     * @var KDCommonCommand
     */
    protected $kdCommon;
    /**
     * @var KuberDock_Api
     */
    protected $api;
    /**
     * @var KuberDock_Api
     */
    protected $adminApi;
    /**
     * @var Assets
     */
    protected $assets;
    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * @return string
     */
    abstract public function getUser();
    /**
     * @return string
     */
    abstract public function getUserGroup();

    /**
     * @return string
     */
    abstract public function getDomain();

    /**
     * @return string
     */
    abstract public function getHomeDir();

    /**
     * @return string
     */
    abstract public function getRootUrl();

    /**
     * @return string
     */
    abstract public function getApiUrl();

    /**
     * @return Assets
     */
    abstract public function getAssets();

    /**
     * @return FileManagerInterface
     */
    abstract public function getFileManager();

    /**
     * @return array
     */
    abstract protected function getAdminData();

    public function __construct()
    {
        $this->user = Base::model()->getStaticPanel()->getUser();
        $this->domain = Base::model()->getStaticPanel()->getDomain();

        $this->adminApi = new KuberDock_Api();
        $adminData = $this->getAdminData();
        $username = isset($adminData['user']) ? $adminData['user'] : '';
        $password = isset($adminData['password']) ? $adminData['password'] : '';
        $token = isset($adminData['token']) ? $adminData['token'] : '';
        $this->adminApi->initAdmin($username, $password, $token);

        $data = $this->adminApi->getInfo($this->user, $this->domain);
        $this->billing = $this->getBilling($data);

        if (!$this->isNoBilling() && ($service = $this->billing->getService())) {
            $token = isset($service['token']) ? $service['token'] : '';
        } else {
            $token = '';
        }

        $this->command = new KcliCommand('', '', $token);
        $this->kdCommon = new KDCommonCommand();

        if (!$this->isNoBilling()) {
            $this->command->setConfig();
        }

        $this->api = new KuberDock_Api();
        $this->api->initUser();
    }

    /**
     * @return KcliCommand
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return KDCommonCommand
     */
    public function getCommonCommand()
    {
        if (!$this->kdCommon) {
            $this->kdCommon = new KDCommonCommand();
        }

        return $this->kdCommon;
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        return $this->api;
    }
    /**
     * @return KuberDock_Api
     */
    public function getAdminApi()
    {
        return $this->adminApi;
    }

    /**
     * @param $data
     * @return WHMCS | BillingInterface
     * @throws CException
     */
    public function getBilling($data)
    {
        if (isset($data['result']) && $data['result'] == 'error') {
            throw new CException($data['message']);
        }

        $billingClasses = array(
            'No billing' => 'NoBilling',
            'WHMCS' => 'WHMCS',
        );

        if (!isset($billingClasses[$data['billing']])) {
            throw new CException('Billing class not exist');
        }

        $className = '\Kuberdock\classes\panels\billing\\' . $billingClasses[$data['billing']];
        return new $className($data);
    }

    /**
     * @return bool
     */
    public function isNoBilling()
    {
        return $this->billing instanceof NoBilling;
    }

    /**
     * @return bool
     */
    public function isUserExists()
    {
        return (bool) $this->getApi()->getToken();
    }

    /**
     * @param bool $root
     * @return string
     */
    public function getURL($root = true)
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $self = explode('/', $_SERVER['PHP_SELF']);
        $self = implode('/', array_slice($self, 0, count($self) - 1));
        $uri = $root ? $this->getRootUrl() : $this->getApiUrl();

        return sprintf('%s://%s:%s%s/%s', $scheme, $host, $port, $self, $uri);
    }

    public static function getClientUrl($action = 'index')
    {
        return $action;
    }

    /**
     * @return array
     */
    public function getUserDomains()
    {
        return $this->kdCommon->getUserDomains();
    }

    /**
     * @return mixed
     */
    public function getUserMainDomain()
    {
        list($domain, $directory) = $this->kdCommon->getUserMainDomain();
        return $domain;
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
            'username' => $this->getUser(),
            'password' => $password,
            'active' => true,
            'rolename' => 'User',
            'package' => $package,
            'email' => $this->getUser() . '@' . $this->getDomain(),
        );

        try {
            $user = $this->getAdminApi()->getUser($this->getUser())->getData();
            throw new CException('User already exists');
        } catch (CException $e) {
            // pass
        }

        $data = $this->getAdminApi()->createUser($data);
        $token = $this->getAdminApi()->getUserToken($this->user, $password);

        $this->command = new KcliCommand('', '', $token);
        $this->command->setConfig();

        return $data;
    }

    /**
     * Headers for Kuberdock\classes\api\KuberDock\* except get_stream()
     * @param int $code
     */
    public function renderResponseHeaders($code)
    {
        header("HTTP/1.1 " . $code . " " . Response::requestStatus($code));
        header("Content-Type: application/json");
    }

    /**
     * Headers for Kuberdock\classes\api\KuberDock\get_stream()
     */
    public function renderStreamHeaders()
    {
        header('Content-Type: text/event-stream');
        // Strange fix, but it works!
        header('X-Accel-Buffering: no');    // for Plesk
    }
}