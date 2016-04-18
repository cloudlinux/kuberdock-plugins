<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;

use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\Tools;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;
use Kuberdock\classes\panels\billing\WHMCS;
use Kuberdock\classes\panels\billing\BillingInterface;
use Kuberdock\classes\panels\billing\NoBilling;

class KuberDock_Plesk
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
     * @throws CException
     */
    public function __construct()
    {
        // TODO: use common cli
        $this->user = $_ENV['REMOTE_USER'];
        $this->domain = $_ENV['DOMAIN'];

        $this->api = new \Kuberdock\classes\components\KuberDock_Api();
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
     * @param $data
     * @return WHMCS | BillingInterface
     * @throws CException
     */
    public function getBilling($data)
    {
        $billingClasses = array(
            'No billing' => 'NoBilling',
            'WHMCS' => 'WHMCS',
        );

        if(!isset($billingClasses[$data['billing']])) {
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
     * @param bool $root
     * @return string
     */
    public function getURL($root = true)
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'];
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $uri = $root ? $this->getRootUrl() : $this->getApiUrl();

        return sprintf('%s://%s:%s%s', $scheme, $host, $port, $uri);
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

        return $data;
    }

    public function updatePod($attributes)
    {
        $data = Base::model()->nativePanel->uapi('KuberDock', 'updatePod', array('data' => json_encode($attributes)));
        return $this->parseModuleResponse($data);
    }

    /**
     * @return bool
     */
    public function isDefaultUser()
    {
        return $this->command->getUsername() == KcliCommand::DEFAULT_USER;
    }
}