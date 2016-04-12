<?php

require dirname(__FILE__) . '/../../../../init.php';
require_once dirname(__FILE__) . '/../init.php';

$opts = getopt('c:', array(
    'service_id:',
));

if (!isset($opts['service_id'])) {
    echo <<<USAGE
Script invoked when user created from KD.

Usage:
php updateUser.php --service_id=1 -c 1

Params:
--service_id        Service ID
-c                  Start count
USAGE;
    exit(0);
}

$serviceId = $opts['service_id'];
$userId = $opts['user_id'];
$startCount = isset($opts['c']) ? $opts['c'] : 1;

try {
    $service = KuberDock_Hosting::model()->loadById($serviceId);
    $client = \base\models\CL_Client::model()->loadById($service->userid);
    $product = KuberDock_Product::model()->loadById($service->packageid);
    $product->setClient($client);
    $client->filterValues();
    $product->create($serviceId);
    exit(0);
} catch(Exception $e) {
    if ($startCount >= 5) {
        \exceptions\CException::log($e);
        exit(1);
    }

    sleep(60);
    $startCount++;
    $command = sprintf('php %s --service_id=%d -c %d', __FILE__, $serviceId, $startCount);
    system($command);
}