<?php

namespace Kuberdock\classes\panels\billing;

use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;
use Kuberdock\classes\Tools;

class WHMCS implements BillingInterface
{
    /**
     * @var array Data from Panel->getInfo()
     */
    private $_data = array();

    /**
     * @param $data
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * @return KuberDock_ApiResponse
     */
    public function getInfo()
    {
        return $this->_data;
    }
    /**
     * @return array
     */
    public function getUserInfo()
    {
        return $this->_data['userDetails'];
    }

    /**
     * @return mixed
     */
    public function getBillingLink()
    {
        return $this->_data['billingLink'];
    }

    /**
     * @return int
     * @throws CException
     */
    public function getUserId()
    {
        $userInfo = $this->getUserInfo();

        if(!isset($userInfo['id'])) {
            throw new CException('Cannot get billing user id');
        }

        return $userInfo['id'];
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->_data['products'];
    }

    /**
     * @return array
     */
    public function getService()
    {
        return $this->_data['service'];
    }

    /**
     * @param array $service
     * @return array
     */
    public function setService($service)
    {
        return $this->_data['service'] = $service;
    }

    /**
     * @return float
     * @throws CException
     */
    public function getUserCredit()
    {
        $userInfo = $this->getUserInfo();

        if(!isset($userInfo['credit'])) {
            throw new CException('Cannot get billing user balance');
        }

        return $userInfo['credit'];
    }

    /**
     * @return array
     * @throws CException
     */
    public function getDefaults() {
        if(isset($this->_data['default'])) {
            return array(
                'packageId' => $this->_data['default']['packageId']['id'],
                'kubeType' => $this->_data['default']['kubeType']['id'],
            );
        } else {
            throw new CException('Cannot get default values. Please fill in defaults via administrator area.');
        }
    }

    /**
     * @param int $packageId
     * @return bool
     */
    public function isFixedPrice($packageId)
    {
        $product = $this->getPackageById($packageId);

        return $product['count_type'] == 'fixed';
    }

    public function getPackage() {
        return current($this->_data['package']);
    }

    public function getPackageById($id)
    {
        if ($this->getPackage()) return $this->getPackage();

        foreach($this->getPackages() as $row) {
            if($row['id'] == $id) {
                return $row;
            }
        }

        throw new CException(sprintf('Package with id: %s not found', $id));
    }

    public function getPackages() {
        return $this->_data['packages'];
    }
}