<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \components\BillingApi::model()->getApiParams($vars);

    foreach (['user', 'userDomains', 'package_id'] as $attr) {
        if (is_null($postFields->params->{$attr})) {
            throw new \Exception(sprintf("Field '%s' required", $attr));
        }
    }

    $packageId = $postFields->params->package_id;
    $user = $postFields->params->user;
    $domains = explode(',', $postFields->params->userDomains);
    $price = $postFields->params->price;    // Not used
    $paymentMethod = $postFields->params->payment_method;   // Not used

    $client = \models\billing\Client::byDomain($user, $domains)->first();

    if (!$client) {
        throw new \exceptions\NotFoundException('User not found');
    }

    $packageRelation = \models\addon\PackageRelation::where('kuber_product_id', $packageId)->first();

    if (!$packageRelation) {
        throw new \exceptions\NotFoundException('Product not found');
    }

    $service = \models\billing\Service::typeKuberDock()
        ->where('userid', $client->id)
        ->where('packageid', $packageRelation->product_id)
        ->where('domainstatus', 'Active')
        ->first();

    if (!$service) {
        $service = \components\BillingApi::model()->createOrder($client, $packageRelation->package);
        $order = \models\billing\Order::find($service->orderid);
        $order = \components\BillingApi::model()->acceptOrder($order);
    } elseif ($service->domainstatus == 'Pending') {
        $order = \models\billing\Order::find($service->orderid);
        $order = \components\BillingApi::model()->acceptOrder($order);
    } else {
        $order = \models\billing\Order::find($service->orderid);
    }

    $results['service_id'] = $service->id;

    if ($order->invoice) {
        $results['status'] = $order->invoice->status;
        if ($order->invoice->isUnpaid()) {
            $url = 'viewinvoice.php?id=' . $order->invoice->id;
            $results['redirect'] = \components\BillingApi::generateAutoAuthLink($url, $client);
        }
    } else {
        $results['status'] = \models\billing\Invoice::STATUS_PAID;
    }

    $apiresults = ['result' => 'success', 'results' => $results];
} catch (Exception $e) {
    $apiresults = ['result' => 'error', 'message' => $e->getMessage()];
}
