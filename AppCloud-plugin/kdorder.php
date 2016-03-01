<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

global $CONFIG;

$ca = new \WHMCS_ClientArea();
$ca->setPageTitle('KuberDock order page');

$ca->addToBreadCrumb('index.php', $whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('kdorder.php', 'KuberDock order');

$ca->initPage();

$base = \base\CL_Base::model();
$base->baseUrl = $CONFIG['SystemURL'];
$base->defaultController = 'KuberDock_Order';
$base->setClientArea($ca);
$base->setSession(session_id());
$base->run();
