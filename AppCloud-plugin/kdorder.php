<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

$ca = new WHMCS_ClientArea();
$templatePath = '../../modules/servers/KuberDock/view/smarty';

$ca->setPageTitle('KuberDock order page');

$ca->addToBreadCrumb('index.php', $whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('kdorder.php', 'KuberDock order');

$ca->initPage();
$ca->requireLogin();

global $CONFIG;

$base = \base\CL_Base::model();
$base->baseUrl = $CONFIG['SystemURL'];
$base->defaultController = 'KuberDock_Order';
$base->run();

//$ca->setTemplate($templatePath . '/kdorder.tpl');
//$ca->output();
