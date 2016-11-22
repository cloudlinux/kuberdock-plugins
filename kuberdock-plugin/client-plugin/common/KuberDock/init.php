<?php

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('KUBERDOCK_ROOT_DIR') or define('KUBERDOCK_ROOT_DIR', __DIR__);
defined('KUBERDOCK_BIN_DIR') or define('KUBERDOCK_BIN_DIR', KUBERDOCK_ROOT_DIR . DS . 'bin');

$dev = KUBERDOCK_ROOT_DIR . DS . 'dev-config.php';

if(file_exists($dev)) {
    include_once $dev;
} else {
    defined('SELECTOR_DEBUG') or define('SELECTOR_DEBUG', false);
    defined('LOG_ERRORS') or define('LOG_ERRORS', true);
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
}

require_once KUBERDOCK_ROOT_DIR . DS . 'classes' . DS . 'KuberDock_AutoLoader.php';

try {
    $loader = new KuberDock_AutoLoader();
    $loader->addNamespace('Kuberdock', KUBERDOCK_ROOT_DIR);

    include_once __DIR__ . '/vendor/autoload.php';

} catch(Exception $e) {
    echo $e->getMessage();
}
