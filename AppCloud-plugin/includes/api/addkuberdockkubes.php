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
            throw new \exceptions\CException(sprintf("Field '%s' is required", $attr));
        }
    }

    $clientId = $postFields->params->client_id;
    $pod= $postFields->params->pod;

    $pod = json_decode(html_entity_decode(urldecode($pod), ENT_QUOTES), true);
    $user = KuberDock_User::model()->loadById($clientId);

    $data = \KuberDock_Addon_Items::model()->loadByAttributes(array(
        'pod_id' => $pod['id'],
    ), '', array(
        'order' => 'id DESC',
        'limit' => 1,
    ));

    if(!$data) {
        throw new Exception('User has no KuberDock item');
    }

    $item = \KuberDock_Addon_Items::model()->loadByParams(current($data));

    if(!$item->isPayed()) {
        throw new Exception('Pod is unpaid');
    }

    if(!$item->service_id) {
        throw new Exception('User has no active KuberDock product');
    }

    $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadById($item->app_id);
    $service = \KuberDock_Hosting::model()->loadById($item->service_id);

    $kdPod = new \KuberDock_Pod($service);
    $kdPod->loadByParams($pod);

    $invoice = $kdPod->updateKubes($predefinedApp->getPod(), $user);

    $results = array(
        'status' => $invoice->status,
        'invoice_id' => $invoice->id,
    );

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
