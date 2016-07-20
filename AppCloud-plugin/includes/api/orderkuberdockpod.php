<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);

    foreach (array('client_id', 'pod') as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $clientId = $postFields->params->client_id;
    $pod = $postFields->params->pod;
    $pod = json_decode(html_entity_decode(urldecode($pod), ENT_QUOTES));

    $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
    $predefinedApp = $predefinedApp->loadByPodId($pod->id);

    if (!$predefinedApp) {
        $predefinedApp = new \KuberDock_Addon_PredefinedApp();
    }

    $data = KuberDock_Hosting::model()->getByUser($clientId);

    if (!$data) {
        throw new Exception('User has no active KuberDock product');
    }

    $service = KuberDock_Hosting::model()->loadByParams(current($data));
    $data = \KuberDock_Addon_Product::model()->loadByAttributes(array(
        'product_id' => $service->packageid,
    ));
    $addonProduct = \KuberDock_Addon_Product::model()->loadByParams(current($data));
    $product = \KuberDock_Product::model()->loadById($service->packageid);

    if (!$product->isFixedPrice()) {
        throw new Exception('Product is not fixed price type');
    }

    if (!isset($product->id)) {
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
            html_entity_decode(urldecode($postFields->params->referer), ENT_QUOTES) : '',
    ));
    $predefinedApp->save();

    $item = \models\addon\Items::unpaid()->where('pod_id', $pod->id)->first();
    /* @var \models\addon\Items $item */

    // Billable item deleted
    if ($item && !$item->billableItem) {
        $item = null;
    }

    if (!$item) {
        $item = $product->addBillableApp($service, $predefinedApp);
    }

    // Get unpaid invoice of divided resources
    $unpaidInvoiceId = (new \models\addon\Resources)->getUnpaidInvoices($pod->id);

    if ($item->isPaid() && !$unpaidInvoiceId) {
        $results = array(
            'status' => $item->status,
            'invoice_id' => $item->invoice_id,
        );
        // Start pod
        $predefinedApp->payAndStart($item->pod_id, $item->service_id);
    } else {
        $user = KuberDock_User::model()->loadById($clientId);

        if ($unpaidInvoiceId) {
            $status = \base\models\CL_Invoice::STATUS_UNPAID;
            $invoiceId = $unpaidInvoiceId;
        } else {
            $status = $item->status;
            $invoiceId = $item->invoice_id;
        }

        $results = array(
            'status' => $status,
            'invoice_id' => $invoiceId,
            'redirect' => \base\CL_Tools::generateAutoAuthLink('viewinvoice.php?id=' . $invoiceId, $user->email),
        );
    }

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
