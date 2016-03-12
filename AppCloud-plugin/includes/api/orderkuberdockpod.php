<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

function getParams($vars) {
    $param = array('action' => array(), 'params' => array());
    $param['action'] = $vars['_POST']['action'];
    unset($vars['_POST']['username']);
    unset($vars['_POST']['password']);
    unset($vars['_POST']['action']);
    $param['params'] = (object) $vars['_POST'];

    return (object) $param;
}

try {
    $vars = get_defined_vars();
    $postFields = getParams($vars);

    foreach(array('client_id', 'pod') as $attr) {
        if(!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $clientId = $postFields->params->client_id;
    $pod= $postFields->params->pod;
    $pod = json_decode(html_entity_decode(urldecode($pod), ENT_QUOTES));

    $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
    $predefinedApp = $predefinedApp->loadByPodId($pod->id);

    if(!$predefinedApp) {
        $predefinedApp = new \KuberDock_Addon_PredefinedApp();
    }

    $data = KuberDock_Hosting::model()->getByUser($clientId);

    if(!$data) {
        throw new Exception('User has no active KuberDock product');
    }

    $service = KuberDock_Hosting::model()->loadByParams(current($data));
    $data = \KuberDock_Addon_Product::model()->loadByAttributes(array(
        'product_id' => $service->packageid,
    ));
    $addonProduct = \KuberDock_Addon_Product::model()->loadByParams(current($data));
    $product = \KuberDock_Product::model()->loadById($service->packageid);

    if(!$product->isFixedPrice()) {
        throw new Exception('Product is not fixed price type');
    }

    if(!isset($product->id)) {
        throw new Exception('Product not found');
    }

    $predefinedApp->setAttributes(array(
        'session_id' => \base\CL_Base::model()->getSession(),
        'data' => json_encode($pod),
        'pod_id' => $pod->id,
        'kuber_product_id' => $addonProduct->kuber_product_id,
        'product_id' => $product->id,
        'referer' => isset($postFields->params->referer) ?
            html_entity_decode(urldecode($postFields->params->referer), ENT_QUOTES) : '',
    ));
    $predefinedApp->save();

    $data = \KuberDock_Addon_Items::model()->loadByAttributes(array(
        'pod_id' => $pod->id,
        'status' => \base\models\CL_Invoice::STATUS_UNPAID,
    ));

    // Billable item deleted
    if($data) {
        $data = current($data);
        $billableItem = \base\models\CL_BillableItems::model()->loadById($data['billable_item_id']);
        if(!$billableItem) {
            $data = null;
        }
    }

    if(!$data) {
        $item = $product->addBillableApp($clientId, $predefinedApp);
    } else {
        $item = \KuberDock_Addon_Items::model()->loadByParams($data);
    }

    if($item->isPayed()) {
        $results = array(
            'status' => $item->status,
            'invoice_id' => $item->invoice_id,
        );
        // Start pod
        $predefinedApp->payAndStart($item->pod_id, $item->service_id);
    } else {
        $results = array(
            'status' => $item->status,
            'invoice_id' => $item->invoice_id,
        );
    }

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
