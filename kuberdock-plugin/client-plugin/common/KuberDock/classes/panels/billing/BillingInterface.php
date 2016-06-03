<?php

namespace Kuberdock\classes\panels\billing;

use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\KuberDock_ApiResponse;

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
     * @return array
     * @throws CException
     */
    public function getDefaults();

    /**
     * @param int $packageId
     * @return bool
     */
    public function isFixedPrice($packageId);
}