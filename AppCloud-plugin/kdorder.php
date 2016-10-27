<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

$ca = new \WHMCS_ClientArea();
$ca->setPageTitle('KuberDock order page');

$ca->addToBreadCrumb('index.php', $whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('kdorder.php', 'KuberDock order');

$ca->initPage();

$controller = new \components\Controller();
$controller->baseUrl = \models\billing\Config::get()->SystemURL;
$controller->defaultController = 'Order';
//$controller->setBillingClientArea($ca);
$controller->run();
