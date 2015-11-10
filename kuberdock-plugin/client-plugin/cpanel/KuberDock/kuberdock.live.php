<?php
include '/usr/local/cpanel/php/cpanel.php';

// Initialize
$dirName = dirname(__FILE__);
include_once $dirName . '/init.php';

try {
    $cPanel = &new CPANEL();
    Base::model()->setPanel($cPanel);
} catch (Exception $e) {
    echo $e->getMessage();
}

// Catch ajax requests
if(Tools::getIsAjaxRequest()) {
    $loader->run();
    $cPanel->end();
    exit();
}

// Header
$cPanel->api1('setvar', '', array('dprefix=../'));

$res = $cPanel->api1('Branding', 'include', array('stdheader.html'));
if($res['cpanelresult']['data']['result']) {
    echo $res['cpanelresult']['data']['result'];
} else {
    echo $cPanel->header('KuberDock plugin', 'KD_PLUGIN');
}

$loader->run();

// Footer
$res = $cPanel->api1('Branding', 'include', array('stdfooter.html') );
if($res['cpanelresult']['data']['result']) {
    echo $res['cpanelresult']['data']['result'];
} else {
    echo $cPanel->footer();
}

$cPanel->end();