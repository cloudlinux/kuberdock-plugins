<?php


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
    public function getCurrency()
    {
        return $this->_data['currency'];
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
    public function getProduct()
    {
        return $this->getProducts() ? current($this->getProducts()) : array();
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
            if($row['kuber_product_id'] == $id) {
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
        return $this->_data['userServices'];
    }

    /**
     * @return array
     */
    public function getService()
    {
        return $this->getServices() ? current($this->_data['userServices']) : array();
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
        $packageAttributes = array('paymentType', 'pricePersistentStorage', 'priceIP', 'billingType', 'restrictedUser');

        foreach($this->getProducts() as $row) {
            $kubes[$row['id']]['currency'] = $currency;
            $kubes[$row['id']]['kubes'] = Tools::getKeyAsField($row['kubes'], 'kuber_kube_id');
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
        $product = $this->getProductById($productId);

        return $product['billingType'] == 'Fixed price';
    }
}