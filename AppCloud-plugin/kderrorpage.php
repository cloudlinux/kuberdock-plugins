<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

$ca = new WHMCS_ClientArea();
$templatePath = '../../modules/servers/KuberDock/view/smarty';

$ca->setPageTitle('Add custom invoice');

$ca->addToBreadCrumb('index.php',$whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('customeinvoice.php','Custom invoice');

$ca->initPage();
$ca->requireLogin();

if(isset($_SESSION['kdError' . session_id()])) {
    $error = $_SESSION['kdError' . session_id()];
} else {
    global $CONFIG;
    header('Location: ' . $CONFIG['SystemURL']);
}

$ca->assign('error', $error);

$ca->setTemplate($templatePath . '/kderrorpage');
$ca->output();
