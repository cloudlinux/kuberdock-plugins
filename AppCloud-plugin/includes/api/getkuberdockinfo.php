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
    global $CONFIG;

    $vars = get_defined_vars();
    $postFields = getParams($vars);
    $kdServer = $postFields->params->kdServer;
    $cpanelUser = $postFields->params->user;
    $cpanelUserDomains = explode(',', $postFields->params->userDomains);

    if(!isset($kdServer)) {
        throw new \exceptions\CException("Field 'kdServer' must be set");
    }

    if(!isset($cpanelUser)) {
        throw new \exceptions\CException("Field 'user' must be set");
    }

    if(!isset($cpanelUserDomains)) {
        throw new \exceptions\CException("Field 'userDomains' must be set");
    }

    $user = \base\models\CL_Client::model()->getClientByCpanelUser($cpanelUser, $cpanelUserDomains);

    $serverIds = array();
    $products = KuberDock_Addon_Product::model()->getByServerUrl($kdServer);

    foreach($products as $row) {
        $serverGroup = KuberDock_ServerGroup::model()->loadById($row['servergroup']);
        $server = $serverGroup->getActiveServer();
        if(!in_array($server->id, $serverIds)) {
            $serverIds[] = $server->id;
        }
    }

    $services = KuberDock_Hosting::model()->getByUser($user['id']);
    $userService = array();

    foreach($services as $row) {
        if(!in_array($row['server'], $serverIds)) {
            continue;
        }

        $model = KuberDock_Hosting::model()->loadByParams($row);
        $userService = array(
            'id' => $model->id,
            'product_id' => $model->packageid,
            'token' => $model->getToken(),
            'domainstatus' => $model->domainstatus,
            'orderid' => $model->orderid,
        );
        if($addonProduct = KuberDock_Addon_Product::model()->loadById($row['packageid'])) {
            $userService['kuber_product_id'] = $addonProduct->kuber_product_id;
        }
    }

    $data['billingUser'] = array(
        'id' => $user['id'],
        'defaultgateway' => $user['defaultgateway'],
    );
    $data['service'] = $userService;
    $data['products'] = KuberDock_Addon_Product::model()->loadByAttributes();
    $data['billing'] = 'WHMCS';
    $data['billingLink'] = $CONFIG['SystemURL'];

    $apiresults = array('result' => 'success', 'results' => $data);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
