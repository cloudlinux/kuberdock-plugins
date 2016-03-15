<?php

include '/usr/local/cpanel/php/cpanel.php';

// Initialize
$dirName = dirname(__FILE__);
include_once $dirName . '/init.php';

try {
    $cPanel = &new \CPANEL();
    \Kuberdock\classes\Base::model()->setNativePanel($cPanel);
} catch (\Exception $e) {
    echo $e->getMessage();
}

if (!file_exists($dev) && !\Kuberdock\classes\Tools::getIsAjaxRequest()) {
    echo json_encode(array('error' => 'ajax requests only'));
    die;
}

try {
    $API = new \Kuberdock\classes\api\KuberDock($_REQUEST['request']);
    echo $API->processAPI();
} catch (\Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}

$cPanel->end();
