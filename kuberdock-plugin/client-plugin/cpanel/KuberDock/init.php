<?php

defined(SELECTOR_DEBUG) or define(SELECTOR_DEBUG, true);

defined(DS) or define(DS, DIRECTORY_SEPARATOR);
defined(KUBERDOCK_ROOT_DIR) or define(KUBERDOCK_ROOT_DIR, dirname(__FILE__));
defined(KUBERDOCK_CLASS_DIR) or define(KUBERDOCK_CLASS_DIR, KUBERDOCK_ROOT_DIR . DS . 'classes');

require_once KUBERDOCK_CLASS_DIR . DS . 'KuberDock_AutoLoader.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $loader = new KuberDock_AutoLoader();
} catch (Exception $e) {
    echo $e->getMessage();
}
