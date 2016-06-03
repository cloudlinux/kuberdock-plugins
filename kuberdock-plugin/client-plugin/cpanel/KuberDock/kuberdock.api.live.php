<?php

use \Kuberdock\classes\api\Response;
use \Kuberdock\classes\Tools;
use \Kuberdock\classes\exceptions\PaymentRequiredException;
use \Kuberdock\classes\exceptions\ApiException;
use \Kuberdock\classes\api\KuberDock;

include '/usr/local/cpanel/php/cpanel.php';
include_once dirname(__FILE__) . '/init.php';

try {
    $cPanel = &new \CPANEL();
    \Kuberdock\classes\Base::model()->setNativePanel($cPanel);

    if (!isset($_REQUEST['request'])) {
        throw new ApiException('Request not found', 404);
    }

    $API = new KuberDock($_REQUEST['request']);
    $API->run();
} catch (PaymentRequiredException $e) {
    Response::error('Payment required', 402, $e->getRedirect());
} catch (ApiException $e) {
    Response::error($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    Response::error($e->getMessage(), 500);
}

$cPanel->end();
