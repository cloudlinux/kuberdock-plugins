<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 01.03.16
 * Time: 23:08
 */
class NoBilling implements BillingInterface
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
        return $this->_data['user'];
    }

    /**
     * @return mixed
     */
    public function getBillingLink()
    {
        return 'No billing';
    }

    /**
     * @return int
     * @throws CException
     */
    public function getUserId()
    {
       return '';
    }

    /**
     * @return array
     */
    public function getCurrency()
    {
        return isset($this->_data['packages']) ? $this->_data['packages'][0] : $this->_data['package'];
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        $_map = array(
            'kube_name' => 'name',
            'kube_price' => 'price',
            'cpu_limit' => 'cpu',
            'memory_limit' => 'memory',
            'traffic_limit' => 'included_traffic',
            'hdd_limit' => 'disk_space',
        );

        if(isset($this->_data['packages'])) {
            $packages = array();
            $_productMap = array(
                'paymentType' => 'period',
                'pricePersistentStorage' => 'price_pstorage',
                'priceIP' => 'price_ip',
            );

            foreach($this->_data['packages'] as &$row) {
                $row['kuber_kube_id'] = $row['id'];
                $kubes = array();

                foreach($_productMap as $pm => $m) {
                    $row[$pm] = $row[$m];
                }

                foreach($row['kubes'] as $kube) {
                    if(!$kube['available']) continue;
                    foreach($_map as $k => $v) {
                        $kube[$k] = $kube[$v];
                    }

                    $kube['kuber_kube_id'] = $kube['id'];
                    $kube['product_id'] = $row['id'];
                    $kubes[$kube['id']] = $kube;
                }
                $row['kubes'] = $kubes;
                $packages[$row['id']] = $row;
            }

            return $packages;
        } else {
            $package = $this->getProduct();
            $package['kuber_kube_id'] = $package['id'];
            $kubes = array();

            foreach($package['kubes'] as $row) {
                if(!$row['available']) continue;

                foreach($_map as $k => $v) {
                    $row[$k] = $row[$v];
                }
                $row['kuber_kube_id'] = $row['id'];
                $row['product_id'] = $package['id'];
                $kubes[$row['id']] = $row;
            }
            $package['kubes'] = $kubes;

            return array(
                $package['id'] => $package,
            );
        }
    }

    /**
     * @return array
     */
    public function getProduct()
    {
        if(isset($this->_data['packages'])) {
            return array();
        }

        $_map = array(
            'paymentType' => 'period',
            'pricePersistentStorage' => 'price_pstorage',
            'priceIP' => 'price_ip',
        );
        foreach($_map as $k => $v) {
            $this->_data['package'][$k] = $this->_data['package'][$v];
        }

        $this->_data['package']['billingType'] = ''; // TODO: fix after field added;

        return $this->_data['package'];
    }

    /**
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductById($id)
    {
        foreach($this->getProducts() as $row) {
            if($row['id'] == $id) {
                return $row;
            }
        }

        throw new CException(sprintf('Package with id: %s not founded', $id));
    }

    /**
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductByKuberId($id)
    {
        foreach($this->getProducts() as $row) {
            if($row['id'] == $id) {
                return $row;
            }
        }

        throw new CException(sprintf('Package with id: %s not founded', $id));
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return array(
            0 => $this->_data['user']
        );
    }

    /**
     * @return array
     */
    public function getService()
    {
        return $this->_data['user'];
    }

    /**
     * @param array $service
     * @return array
     */
    public function setService($service)
    {
        return $this->_data['userServices'] = $service;
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
     * array
     */
    public function getKubes()
    {
        $kubes = array();
        $currency = $this->getCurrency();
        $packageAttributes = array('paymentType', 'pricePersistentStorage', 'priceIP', 'billingType');

        foreach($this->getProducts() as $row) {
            $kubes[$row['id']]['currency'] = $currency;
            $kubes[$row['id']]['kubes'] = $row['kubes'];
            $kubes[$row['id']]['product_id'] = $row['id'];
            foreach($packageAttributes as $attr) {
                $kubes[$row['id']][$attr] = $row[$attr];
            }
        }

        return $kubes;
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
     * @param int $productId
     * @return bool
     */
    public function isFixedPrice($productId)
    {
        return false;
    }
}