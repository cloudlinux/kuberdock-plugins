<?php
/**
 * @project whmcs-plugin
 */

namespace base\models;

use base\CL_Model;

class CL_Order extends CL_Model {

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblorders';
    }

    /**
     * @param int $userId
     * @param int $productId
     * @return array
     * @throws \Exception
     */
    public function createOrder($userId, $productId)
    {
        $admin = \KuberDock_User::model()->getCurrentAdmin();
        $user = \KuberDock_User::model()->loadById($userId);

        if($user->defaultgateway) {
            $paymentMethod = $user->defaultgateway;
        } else {
            $gateways = CL_Currency::model()->getPaymentGateways();
            $paymentMethod = current($gateways)['module'];
        }

        $values['clientid'] = $userId;
        $values['pid'] = $productId;
        $values['paymentmethod'] = $paymentMethod;

        $results = localAPI('addorder', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new \Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param int $orderId
     * @return array
     * @throws \Exception
     */
    public function acceptOrder($orderId)
    {
        $admin = \KuberDock_User::model()->getCurrentAdmin();

        $values = array(
            'orderid' => $orderId,
            'autosetup' => true,
            'sendemail' => true,
        );

        $results = localAPI('acceptorder', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new \Exception($results['message']);
        }

        return $results;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(!isset(self::$_models[$className])) {
            self::$_models[$className] = new $className;
        }

        return self::$_models[$className];
    }
} 