<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);

    foreach (array('client_id', 'pod', 'oldPod') as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' is required", $attr));
        }
    }

    $clientId = $postFields->params->client_id;
    $pod = $postFields->params->pod;
    $oldPod = $postFields->params->oldPod;
    $pod = json_decode(html_entity_decode(urldecode($pod), ENT_QUOTES), true);
    $oldPod = json_decode(html_entity_decode(urldecode($oldPod), ENT_QUOTES), true);

    $user = KuberDock_User::model()->loadById($clientId);

    $data = \KuberDock_Addon_Items::model()->loadByAttributes(array(
        'pod_id' => $pod['id'],
    ), '', array(
        'order' => 'id DESC',
        'limit' => 1,
    ));

    if (!$data) {
        throw new Exception('User has no KuberDock item');
    }

    $item = \KuberDock_Addon_Items::model()->loadByParams(current($data));

    if (!$item->isPayed()) {
        throw new Exception('Pod is unpaid');
    }

    if (!$item->service_id) {
        throw new Exception('User has no active KuberDock product');
    }

    $service = \KuberDock_Hosting::model()->loadById($item->service_id);
    if ($service == false) {
        throw new Exception('Service not found');
    }

    $kdPod = new \KuberDock_Pod($service);
    $kdPod->loadByParams($oldPod);

    $invoice = $kdPod->edit($pod, $user);

    $results = array(
        'status' => $invoice->status,
        'invoice_id' => $invoice->id,
    );

    if (!$invoice->isPayed()) {
        // Switch plan for PA, mark pod unpaid
        try {
            $service->getApi()->stopPod($pod['id']);
        } catch (Exception $e) {
            // pass if pod already stopped
        }

        $service->getAdminApi()->updatePod($pod['id'], array(
            'status' => 'unpaid',
        ));
        $results['redirect'] = \base\CL_Tools::generateAutoAuthLink('viewinvoice.php?id=' . $invoice->id, $user->email);
    }

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
