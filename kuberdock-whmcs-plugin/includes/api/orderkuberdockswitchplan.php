<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \components\BillingApi::model()->getApiParams($vars);

    foreach (['client_id', 'pod', 'oldPod'] as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' is required", $attr));
        }
    }

    $service = \models\billing\Service::typeKuberDock()->where('userid', $postFields->params->client_id)->first();

    if (!$service) {
        throw new Exception('User has no KuberDock service');
    }

    $oldPod = html_entity_decode(urldecode($postFields->params->oldPod), ENT_QUOTES);
    $newPod = html_entity_decode(urldecode($postFields->params->pod), ENT_QUOTES);

    $pod = new \models\addon\resource\Pod($service->package);
    $pod->load($oldPod);
    $pod->edited_config = json_decode($newPod, true);
    $pod->setReferer($postFields->params->referer);

    $results = $service->package->getBilling()->processApiOrder($pod, $service, \models\addon\ItemInvoice::TYPE_SWITCH);

    $apiresults = ['result' => 'success', 'results' => $results];
} catch (Exception $e) {
    $apiresults = ['result' => 'error', 'message' => $e->getMessage()];
}
