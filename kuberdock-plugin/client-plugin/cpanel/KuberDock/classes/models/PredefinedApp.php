<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */


class PredefinedApp {
    /**
     * @var array
     */
    public $kuberProducts = array();
    /**
     * @var array
     */
    public $kuberKubes = array();
    /**
     * @var array
     */
    public $userKuberProduct = array();
    /**
     * @var int
     */
    public $billingClientId;
    /**
     * KuberDock token
     * @var string
     */
    private $token;

    /**
     * @var WHMCSApi
     */
    private $api;
    /**
     * @var KcliCommand
     */
    private $userCommand;
    /**
     * @var KcliCommand
     */
    private $adminCommand;
    /**
     * @var array
     */
    private $_data = array(
        'containers' => array(),
    );

    /**
     *
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @param $name
     * @return ArrayObject|mixed
     */
    public function __get($name)
    {
        $methodName = 'get'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new ReflectionMethod($this, $methodName);
            return $rm->invoke($this);
        } elseif(isset($this->_data[$name])) {
            if(is_array($this->_data[$name])) {
                return new ArrayObject($this->_data[$name]);
            } else {
                return $this->_data[$name];
            }
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $methodName = 'set'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new ReflectionMethod($this, $methodName);
            return $rm->invoke($this, $value);
        } else {
            $this->_data[$name] = $value;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     *
     */
    public function init()
    {
        $this->api = WHMCSApi::model();
        $this->userKuberProduct = $this->api->getUserKuberDockProduct();
        $this->kuberProducts = $this->api->getKuberDockProducts();
        $this->kuberKubes = $this->api->getKuberKubes();
        $this->billingClientId = $this->api->getWHMCSClientId();
        $this->setToken();

        list($username, $password) = $this->api->getAuthData();
        $this->userCommand = new KcliCommand($username, $password, $this->token);

        list($username, $password) = $this->api->getAdminAuthData();
        $this->adminCommand = new KcliCommand($username, $password);
    }

    /**
     *
     */
    public function setToken()
    {
        $data = $this->api->request(array(
            'clientid' => $this->billingClientId,
        ), 'getclientskuberproducts');

        if(isset($data['results']) && $data['results']) {
            $this->token = current($data['results'])['token'];
        }
    }

    public function getTemplate($id)
    {
        $template = $this->adminCommand->getYAMLTemplate($id);

        if(!$template) {
            throw new CException('Template not exists');
        }
    }
} 