<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

$ca = new WHMCS_ClientArea();
$templatePath = '../../modules/servers/KuberDock/view/smarty';

$ca->setPageTitle('KuberDock error page');

$ca->addToBreadCrumb('index.php',$whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('kderrorpage.php', 'KuberDock errors');

$ca->initPage();

if(isset($_SESSION['kdError' . session_id()])) {
    $error = $_SESSION['kdError' . session_id()];
} else {
    global $CONFIG;
    header('Location: ' . $CONFIG['SystemURL']);
}

$ca->assign('error', $error);

$ca->setTemplate($templatePath . '/kderrorpage');
$ca->output();
