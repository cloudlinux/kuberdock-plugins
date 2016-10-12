<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

defined(DS) or define(DS, DIRECTORY_SEPARATOR);
defined(KUBERDOCK_MODULE_NAME) or define(KUBERDOCK_MODULE_NAME, 'KuberDock');
defined(KUBERDOCK_ROOT_DIR) or define(KUBERDOCK_ROOT_DIR, dirname(__FILE__));
defined(KUBERDOCK_CLASS_DIR) or define(KUBERDOCK_CLASS_DIR, KUBERDOCK_ROOT_DIR . DS . 'classes');

defined(KUBERDOCK_DEBUG) or define(KUBERDOCK_DEBUG, true);
defined(KUBERDOCK_DEBUG_API) or define(KUBERDOCK_DEBUG_API, false);

// Enable JWT tokens for KD API requests
defined(USE_JWT_TOKENS) or define(USE_JWT_TOKENS, true);

// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

if(KUBERDOCK_DEBUG) {
    ini_set('display_errors', true);
    error_reporting(E_ERROR);
} else {
    ini_set('display_errors', false);
    error_reporting(E_ERROR);
}

require_once KUBERDOCK_CLASS_DIR . DS . 'KuberDock_AutoLoader.php';

try {
    $loader = new KuberDock_AutoLoader();
} catch(Exception $e) {
    echo $e->getMessage();
    \exceptions\CException::log($e);
}
