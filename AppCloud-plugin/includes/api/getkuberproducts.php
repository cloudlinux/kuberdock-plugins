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

    $currency = CL_Currency::model()->getDefaultCurrency();
    $products = KuberDock_Product::model()->loadByAttributes(array(
        'servertype' => KUBERDOCK_MODULE_NAME
    ), 'servergroup > 0');

    foreach($products as &$row) {
        $row['currency'] = $currency->getAttributes();
        $product = KuberDock_Product::model()->loadByParams($row);
        $row['kubes'] = $product->getKubes();

        $i = 1;
        foreach($product->getConfig() as $option => $settings) {
            $row[$option] = $product->{'configoption' . $i};
            $i++;
        }

        $serverGroup = KuberDock_ServerGroup::model()->loadById($product->servergroup);
        if($serverGroup) {
            try {
                $server = $serverGroup->getActiveServer();
                $row['server'] = $server->getAttributes();
                $row['serverFullUrl'] = $server->getApiServerUrl();
                $row['server']['password'] = $server->decryptPassword();
            } catch(Exception $e) {
                unset($row);
            }
        }
    }

    $apiresults = array('result' => 'success', 'results' => $products);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}