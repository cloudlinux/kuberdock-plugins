<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \base\CL_Tools::getApiParams($vars);

    $predefinedApp = \KuberDock_Addon_PredefinedApp::model();

    foreach (array($predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD, $predefinedApp::KUBERDOCK_YAML_FIELD) as $attr) {
        if (!isset($postFields->params->{$attr})) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $kdProductId = $postFields->params->{$predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD};
    $yaml = html_entity_decode(urldecode($postFields->params->{$predefinedApp::KUBERDOCK_YAML_FIELD}), ENT_QUOTES);
    $referer = $postFields->params->{$predefinedApp::KUBERDOCK_REFERER_FIELD};
    $parsedYaml = \Spyc::YAMLLoadString($yaml);

    if(isset($parsedYaml['kuberdock']['packageID'])) {
        $kdProductId = $parsedYaml['kuberdock']['packageID'];
    }

    if (!$referer) {
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            $referer = $_SERVER['HTTP_REFERER'];
        } elseif (isset($parsedYaml['kuberdock']['server'])) {
            $referer = $parsedYaml['kuberdock']['server'];
        } elseif ($server = \KuberDock_Server::model()->getActive()) {
            $referer = $server->getApiServerUrl();
        } else {
            throw new \exceptions\CException('Cannot get KuberDock server url');
        }
    }

    $kdProduct = \KuberDock_Addon_Product::model()->getByKuberId($kdProductId, $referer);
    $product = \KuberDock_Product::model()->loadById($kdProduct->product_id);

    $predefinedApp = $predefinedApp->loadBySessionId();

    if (!$predefinedApp) {
        $predefinedApp = new \KuberDock_Addon_PredefinedApp();
    }

    $sessionId = \base\CL_Base::model()->getSession();

    $predefinedApp->setAttributes(array(
        'session_id' => $sessionId,
        'kuber_product_id' => $kdProductId,
        'product_id' => $product->id,
        'data' => $yaml,
    ));

    $predefinedApp->save();

    $config = \base\models\CL_Configuration::model()->get();

    $results = array(
        'package_id' => $kdProductId,
        'redirect' => sprintf('%s/kdorder.php?a=toCart&sessionId=%s', $config->SystemURL, $sessionId),
    );

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
