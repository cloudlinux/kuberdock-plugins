<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \components\BillingApi::model()->getApiParams($vars);

    foreach (['pkgid', 'yaml'] as $attr) {
        if (!isset($postFields->params->{$attr})) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $packageId = $postFields->params->pkgid;
    $yaml = html_entity_decode(rawurldecode($postFields->params->yaml), ENT_QUOTES);
    $referer = $postFields->params->referer;
    $parsedYaml = \components\Tools::parseYaml($yaml);

    if (isset($parsedYaml['kuberdock']['packageID'])) {
        $packageId = $parsedYaml['kuberdock']['packageID'];
    }

    $packageRelation = \models\addon\PackageRelation::where('kuber_product_id', $packageId)
        ->byReferer($referer)->first();

    if (!$packageRelation) {
        throw new Exception(sprintf('Product for KuberDock server %s not found', $referer));
    }

    $app = new \models\addon\App();
    $app->setRawAttributes([
        'kuber_product_id' => $packageRelation->kuber_product_id,
        'product_id' => $packageRelation->product_id,
        'data' => $yaml,
        'referer' => $referer,
        'type' => \models\addon\resource\ResourceFactory::TYPE_YAML,
    ]);

    $app->save();

    $config = \models\billing\Config::get();

    $results = array(
        'redirect' => sprintf('%s/kdorder.php?a=toCart&id=%s', $config->SystemURL, $app->id),
    );

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
