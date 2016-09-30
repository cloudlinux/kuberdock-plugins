<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);

    // Used for local api call
    if (empty((array) $postFields->params) && isset($_POST['client_id']) && isset($_POST['pod'])) {
        $postFields->params->client_id = (int) $_POST['client_id'];
        $postFields->params->pod = $_POST['pod'];
        $postFields->params->referer = isset($_POST['referer']) ? $_POST['referer'] : '';
    }

    foreach(array('client_id', 'pod') as $attr) {
        if(!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $clientId = $postFields->params->client_id;
    $pod= $postFields->params->pod;
    $pod = json_decode(html_entity_decode(rawurldecode($pod), ENT_QUOTES));

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
        'user_id' => $clientId,
        'referer' => isset($postFields->params->referer) ?
            html_entity_decode(rawurldecode($postFields->params->referer), ENT_QUOTES) : '',
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
        $user = KuberDock_User::model()->loadById($clientId);
        $results = array(
            'status' => $item->status,
            'invoice_id' => $item->invoice_id,
            'redirect' => \base\CL_Tools::generateAutoAuthLink('viewinvoice.php?id=' . $item->invoice_id, $user->email),
        );
    }

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
