<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    global $CONFIG;

    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);

    foreach(array('user', 'userDomains', 'package_id') as $attr) {
        if(is_null($postFields->params->{$attr})) {
            throw new \Exception(sprintf("Field '%s' required", $attr));
        }
    }

    $packageId = $postFields->params->package_id;
    $user = $postFields->params->user;
    $userDomains = explode(',', $postFields->params->userDomains);
    $price = $postFields->params->price;
    $paymentMethod = $postFields->params->payment_method;

    $userData = \base\models\CL_Client::model()->getClientByCpanelUser($user, $userDomains);
    $services = \KuberDock_Hosting::model()->getByUser($userData['userid']);
    $service = current($services);

    $addonProduct = KuberDock_Addon_Product::model()->loadByAttributes(array(
        'kuber_product_id' => $packageId,
    ));

    if (!$addonProduct) {
        throw new \Exception('Product not found');
    }

    $addonProduct = current($addonProduct);
    $product = KuberDock_Product::model()->loadById($addonProduct['product_id']);
    $results = array();

    if (!$service) {
        $order = \base\models\CL_Order::model()->createOrder($userData['userid'], $product->id, $price);
        $orderId = $order['orderid'];
        $serviceId = $order['productids'];
    } else if ($service['domainstatus'] == KuberDock_User::STATUS_PENDING) {
        $order = \base\models\CL_Order::model()->getOrders($service['orderid']);
        $order = current($order['orders']['order']);
        $orderId = $order['id'];
        $serviceId = $service['id'];
    } else {
        $order = null;
        $serviceId = $service['id'];
    }

    $results['service_id'] = $serviceId;
    if ($order) {
        if ($order['invoiceid'] > 0) {
            $invoice = \base\models\CL_Invoice::model()->loadById($order['invoiceid']);
            if (!$invoice->isPayed()) {
                $results['status'] = \base\models\CL_Invoice::STATUS_UNPAID;
                $results['redirect'] = \base\CL_Tools::generateAutoAuthLink('viewinvoice.php?id=' . $invoice->id, $userData['email']);
            } else {
                \base\models\CL_Order::model()->acceptOrder($orderId);
                $results['status'] = \base\models\CL_Invoice::STATUS_PAID;
            }
        } else {
            \base\models\CL_Order::model()->acceptOrder($orderId);
            $results['status'] = \base\models\CL_Invoice::STATUS_PAID;
        }
    } else {
        $results['status'] = \base\models\CL_Invoice::STATUS_PAID;
    }

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
