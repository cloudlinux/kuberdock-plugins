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

    if(!isset($postFields->params->clientid)) {
        throw new Exception('Field \'clientid\' must be set');
    }

    $services = KuberDock_Hosting::model()->getByUser($postFields->params->clientid);

    array_walk($services, function(&$e) {
        $model = KuberDock_Hosting::model()->loadByParams($e);
        $e['token'] = $model->getToken();
    });

    $apiresults = array('result' => 'success', 'results' => $services);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}