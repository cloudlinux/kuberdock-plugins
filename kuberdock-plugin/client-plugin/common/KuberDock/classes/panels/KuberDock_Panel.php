<?php

namespace Kuberdock\classes\panels;

use Kuberdock\classes\components\KuberDock_Api;

use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KDCommonCommand;
use Kuberdock\classes\KuberDock_Assets;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;
use Kuberdock\classes\panels\billing\WHMCS;
use Kuberdock\classes\panels\billing\BillingInterface;
use Kuberdock\classes\panels\billing\NoBilling;

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
     * @var KuberDock_Assets
     */
    protected $assets;

    /**
     * @return string
     */
    abstract public function getUser();

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
     * @return KuberDock_Assets
     */
    abstract public function getAssets();

    /**
     * @param string $package
     * @return mixed
     * @throws CException
     */
    abstract public function createUser($package);

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