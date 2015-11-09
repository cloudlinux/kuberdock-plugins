<?php

defined(DS) or define(DS, DIRECTORY_SEPARATOR);
defined(KUBERDOCK_ROOT_DIR) or define(KUBERDOCK_ROOT_DIR, dirname(__FILE__));
defined(KUBERDOCK_CLASS_DIR) or define(KUBERDOCK_CLASS_DIR, KUBERDOCK_ROOT_DIR . DS . 'classes');

$dev = KUBERDOCK_ROOT_DIR . DS . 'dev-config.php';
if(file_exists($dev)) {
    include_once $dev;
} else {
    defined(SELECTOR_DEBUG) or define(SELECTOR_DEBUG, false);
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
}

require_once KUBERDOCK_CLASS_DIR . DS . 'KuberDock_AutoLoader.php';

try {
    $loader = new KuberDock_AutoLoader();
} catch (Exception $e) {
    echo $e->getMessage();
}
