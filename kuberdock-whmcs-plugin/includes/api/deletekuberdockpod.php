<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../modules/servers/KuberDock/init.php';

$apiresults = \api\whmcs\DeletePod::call(get_defined_vars());
