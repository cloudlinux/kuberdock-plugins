<?php


class KuberDock_CPanel
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
     * @var WHMCS | BillingInterface
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

        $this->api = new KuberDock_Api();
        $data = $this->api->getInfo($this->user, $this->domain);
        $this->billing = $this->getBilling($data);

        //echo '<pre>'; print_r($data);

        if($service = $this->billing->getService()) {
            $username = isset($service['username']) ? $service['username'] : '';
            $password = isset($service['password']) ? $service['password'] : '';
            $token = isset($service['token']) ? $service['token'] : '';;
        } else {
            $username = KcliCommand::DEFAULT_USER;
            $password = KcliCommand::DEFAULT_USER;
            $token = '';
        }

        $this->command = new KcliCommand($username, $password, $token);
        $this->kdCommon = new KDCommonCommand();

        // TODO: save only after create user (buy product)
        if(!$this->isNoBilling()) {
            $this->command->setConfig();
        }
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

        return new $billingClasses[$data['billing']]($data);
    }

    public function isNoBilling()
    {
        return $this->billing instanceof NoBilling;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'];
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $uri = $_SERVER['SCRIPT_URI'];

        return sprintf('%s://%s:%s%s', $scheme, $host, $port, $uri);
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
}