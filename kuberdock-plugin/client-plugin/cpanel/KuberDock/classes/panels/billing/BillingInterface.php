<?php


interface BillingInterface
{
    /**
     * @param $data
     */
    public function __construct($data);

    /**
     * @return KuberDock_ApiResponse
     */
    public function getInfo();

    /**
     * @return array
     */
    public function getUserInfo();

    /**
     * @return mixed
     */
    public function getBillingLink();

    /**
     * @return int
     * @throws CException
     */
    public function getUserId();

    /**
     * @return array
     */
    public function getCurrency();

    /**
     * @return array
     */
    public function getProducts();

    /**
     * @return array
     */
    public function getProduct();

    /**
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductById($id);

    /**
     * @param int $id
     * @return array
     * @throws CException
     */
    public function getProductByKuberId($id);

    /**
     * @return array
     */
    public function getServices();

    /**
     * @return array
     */
    public function getService();

    /**
     * @param array $service
     * @return array
     */
    public function setService($service);

    /**
     * @return float
     * @throws CException
     */
    public function getUserCredit();

    /**
     * array
     */
    public function getKubes();

    /**
     * @return array
     * @throws CException
     */
    public function getDefaults();

    /**
     * @param int $productId
     * @return bool
     */
    public function isFixedPrice($productId);
}