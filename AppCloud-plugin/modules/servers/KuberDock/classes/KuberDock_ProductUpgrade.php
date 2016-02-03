<?php

use base\models\CL_ProductUpgrade;
use base\models\CL_Invoice;
use base\models\CL_Client;
use base\CL_Tools;
use exceptions\CException;

class KuberDock_ProductUpgrade extends CL_ProductUpgrade {
    /**
     *
     */
    const STATUS_COMPLETE = 'Completed';
    /**
     *
     */
    const STATUS_PENDING = 'Pending';

    /**
     * @param int $serviceId
     * @return $this
     * @throws CException
     */
    public function loadByServiceId($serviceId)
    {
        $data = $this->loadByAttributes(array(
            'relid' => $serviceId,
            'status' => self::STATUS_PENDING,
            'type' => 'package',
            'orderid' => 0,
        ), '', array(
            'order' => 'id DESC',
            'limit' => 1
        ));

        if(!$data) {
            throw new CException('Upgrade package info not founded');
        }

        return $this->loadByParams(current($data));
    }

    /**
     * @throws Exception
     */
    public function changePackage()
    {
        $oldProduct = KuberDock_Product::model()->loadById($this->originalvalue);
        $newProduct = $this->getNewProduct();

        $deposit = KuberDock_Product::model()->getConfigOption('firstDeposit');
        if($deposit) {
            $service = KuberDock_Hosting::model()->loadById($this->relid);
            $clientDetails = CL_Client::model()->getClientDetails($service->userid);

            if($clientDetails['client']['credit'] < $deposit) {
                $service->addInvoice($service->userid, new \DateTime(), $deposit, false, CL_Invoice::CUSTOM_INVOICE_DESCRIPTION);
                $service->suspendModule('Not enough funds');
                return false;
            }
            $service->addInvoice($service->userid, new \DateTime(), $deposit, true, CL_Invoice::CUSTOM_INVOICE_DESCRIPTION);
        }

        if($oldProduct->getConfigOption('paymentType') == 'hourly' && $newProduct->getConfigOption('paymentType') != 'hourly') {
            // nothing
            //return $this->calculateFromHourToPeriodic();
        } elseif($oldProduct->getConfigOption('paymentType') != 'hourly' && $newProduct->getConfigOption('paymentType') == 'hourly') {
            return $this->calculateFromPeriodicToHour();
        }
    }

    /**
     * @return KuberDock_Product
     */
    public function getNewProduct()
    {
        list($productId, $payment) = explode(',', $this->newvalue);

        return KuberDock_Product::model()->loadById($productId);
    }

    /**
     *
     */
    private function calculateFromHourToPeriodic()
    {
    }

    /**
     * @throws Exception
     */
    private function calculateFromPeriodicToHour()
    {
        $service = KuberDock_Hosting::model()->loadById($this->relid);
        $states = KuberDock_Addon_States::model()->getLastStateByServiceId($this->relid);
        $product = KuberDock_Product::model()->loadById($states->product_id);

        $currentDate = new DateTime();
        $endPeriodDate = CL_Tools::model()->sqlDateToDateTime($states->checkin_date);

        $days = (int) $endPeriodDate->diff($currentDate)->format("%R%a");
        if($days) {
            $sum = $states->total_sum / $product->getPeriodInDays() * $days;
            CL_Invoice::model()->addCredit($service->userid, $sum, 'Adding funds for package change');
        }
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
}