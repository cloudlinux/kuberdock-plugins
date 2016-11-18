<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \components\BillingApi::model()->getApiParams($vars);

    foreach (['kdServer', 'user', 'userDomains'] as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $kdServer = $postFields->params->kdServer;
    $user = $postFields->params->user;
    $domains = explode(',', $postFields->params->userDomains);

    $client = \models\billing\Client::byDomain($user, $domains)->first();

    if (!$client) {
        throw new \exceptions\NotFoundException('User not found. Probably you have no service with your current domain.');
    }

    $packageRelation = \models\addon\PackageRelation::byReferer($kdServer)->count();
    $server = \models\billing\Server::typeKuberDock()->byReferer($kdServer)->first();

    if (!$packageRelation) {
        throw new \exceptions\NotFoundException(sprintf('KuberDock product for server %s not found', $kdServer));
    }

    $data = [];

    $adminApi = $server->getApi();

    $service = \models\billing\Service::where('userid', $client->id)
        ->where('server', $server->id)
        ->where('domainstatus', 'Active')
        ->orderBy('id', 'desc')
        ->first();

    if ($service) {
        $kdPackageId = $service->package->relatedKuberDock->kuber_product_id;
        $data['service'] = [
            'id' => $service->id,
            'product_id' => $service->packageid,
            'token' => $service->getToken(),
            'domainstatus' => $service->domainstatus,
            'orderid' => $service->orderid,
            'kuber_product_id' => $kdPackageId,
        ];
        $data['package'] = $service->getAdminApi()->getPackageById($kdPackageId, true)->getData();
    } else {
        $data['packages'] = $adminApi->getPackages(true)->getData();
    }

    $data['billingUser'] = [
        'id' => $client->id,
        'defaultgateway' => $client->defaultgateway,
    ];

    $data['billing'] = 'WHMCS';
    $data['billingLink'] = \models\billing\Config::get()->SystemURL;

    $data['default']['kubeType'] = $adminApi->getDefaultKubeType()->getData();
    $data['default']['packageId'] = $adminApi->getDefaultPackageId()->getData();

    $apiresults = ['result' => 'success', 'results' => $data];
} catch (Exception $e) {
    $apiresults = ['result' => 'error', 'message' => $e->getMessage()];
}
