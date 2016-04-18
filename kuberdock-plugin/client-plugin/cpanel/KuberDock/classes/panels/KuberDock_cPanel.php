<?php


class KuberDock_cPanel
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
     * @var KuberDock_Api
     */
    private $adminApi;

    /**
     * @throws CException
     */
    public function __construct()
    {
        $this->user = $_ENV['REMOTE_USER'];
        $this->domain = $_ENV['DOMAIN'];

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
        $this->command->setConfig();
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
     * @return KuberDock_Api
     */
    public function getAdminApi()
    {
        return $this->adminApi;
    }

    public function isUserExists()
    {
        return (bool) $this->getApi()->getToken();
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

    /**
     * @return bool
     */
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

    private function getAdminData()
    {
        $data = Base::model()->nativePanel->uapi('KuberDock', 'getAdminData');
        return $this->parseModuleResponse($data);
    }
}