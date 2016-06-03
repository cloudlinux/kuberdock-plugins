<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    global $CONFIG;

    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);
    $kdServer = $postFields->params->kdServer;
    $cpanelUser = $postFields->params->user;
    $cpanelUserDomains = explode(',', $postFields->params->userDomains);

    foreach (array('kdServer', 'user', 'userDomains') as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $kdServer = $postFields->params->kdServer;
    $hUser = $postFields->params->user;
    $hUserDomains = explode(',', $postFields->params->userDomains);

    $user = \base\models\CL_Client::model()->getClientByCpanelUser($hUser, $hUserDomains);

    $server = KuberDock_Server::model()->getByUrl($kdServer);
    $adminApi = $server->getApi();
    $services = KuberDock_Hosting::model()->getByUser($user['id']);
    $userService = array();

    foreach ($services as $row) {
        if ($row['server'] != $server->id) {
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
        if ($addonProduct = KuberDock_Addon_Product::model()->loadById($row['packageid'])) {
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

    $data['default']['kubeType'] = $adminApi->getDefaultKubeType()->getData();
    $data['default']['packageId'] = $adminApi->getDefaultPackageId()->getData();
    if ($userService) {
        $data['package'][] = $adminApi->getPackageById($userService['kuber_product_id'], true)->getData();
    } else {
        $data['packages'] = $adminApi->getPackages(true)->getData();
    }

    $apiresults = array('result' => 'success', 'results' => $data);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
