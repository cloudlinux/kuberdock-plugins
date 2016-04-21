<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $results = KuberDock_config();

    $apiresults = array('result' => 'success', 'results' => $results);
} catch (Exception $e) {
    $apiresults = array('result' => 'error', 'message' => $e->getMessage());
}
