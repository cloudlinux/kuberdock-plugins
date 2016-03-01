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
    $kdServer = $postFields->params->kdServer;
    $cpanelUser = $postFields->params->user;
    $cpanelUserDomains = explode(',', $postFields->params->userDomains);

    $userData['currency'] = \base\models\CL_Currency::model()->getDefaultCurrency()->getAttributes();

    if(!isset($kdServer)) {
        throw new \exceptions\CException("Field 'kdServer' must be setted");
    }

    if(!isset($cpanelUser)) {
        throw new \exceptions\CException("Field 'user' must be setted");
    }

    if(!isset($cpanelUserDomains)) {
        throw new \exceptions\CException("Field 'userDomains' must be setted");
    }

    $userData['userDetails'] = \base\models\CL_Client::model()->getClientByCpanelUser($cpanelUser, $cpanelUserDomains);

    $serverIds = array();
    $products = KuberDock_Addon_Product::model()->getByServerUrl($kdServer);

    foreach($products as &$row) {
        $product = KuberDock_Product::model()->loadByParams($row);
        $row['kubes'] = $product->getKubes();

        $i = 1;
        foreach($product->getConfig() as $option => $settings) {
            $row[$option] = $product->{'configoption' . $i};
            $i++;
        }

        $serverGroup = KuberDock_ServerGroup::model()->loadById($product->servergroup);
        $server = $serverGroup->getActiveServer();
        if(!in_array($server->id, $serverIds)) {
            $serverIds[] = $server->id;
        }
    }

    $services = KuberDock_Hosting::model()->getByUser($userData['userDetails']['userid']);
    $userServices = array();
    foreach($services as &$row) {
        if(in_array($row['server'], $serverIds)) {
            $model = KuberDock_Hosting::model()->loadByParams($row);
            $row['password'] = $model->decryptPassword();
            $row['token'] = $model->getToken();
            if($addonProduct = KuberDock_Addon_Product::model()->loadById($e['packageid'])) {
                $e['kuber_product_id'] = $addonProduct->kuber_product_id;
            }
            $userServices[$row['product_id']] = $row;
        }
    }

    // If user has product, remove others
    if($userServices) {
        $products = array_filter($products, function ($e) use ($services) {
            foreach ($services as $row) {
                if ($row['packageid'] == $e['id']) {
                    return $e;
                }
            }
        });
    }

    $userData['userServices'] = $userServices;
    $userData['products'] = $products;

    try {
        $api = \api\KuberDock_Api::constructByServer($server);
        $kubeType = $api->getDefaultKubeType();
        $PackageId = $api->getDefaultPackageId();
        $userData['default']['kubeType'] = $kubeType->parsed['data'];
        $userData['default']['packageId'] = $PackageId->parsed['data'];
    } catch (Exception $e) {
        $userData['default'] = null;
    }

    global $CONFIG;
    $userData['billing'] = 'WHMCS';
    $userData['billingLink'] = $CONFIG['SystemURL'];

    $apiresults = array('result' => 'success', 'results' => $userData);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
