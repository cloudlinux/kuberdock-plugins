<?php
include '/usr/local/cpanel/php/cpanel.php';

// Initialize
$dirName = dirname(__FILE__);
include_once $dirName . '/init.php';

try {
    $cPanel = &new \CPANEL();
    \Kuberdock\classes\Base::model()->setNativePanel($cPanel);
} catch (Exception $e) {
    echo $e->getMessage();
}

$run = function() {
    $controller = isset($_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_PARAM])
        ? $_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_PARAM]
        : 'default';

    $action = isset($_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_ACTION_PARAM])
        ? $_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_ACTION_PARAM]
        : 'index';

    try {
        $className = '\Kuberdock\classes\controllers\\' . ucfirst($controller) . 'Controller';
        $model = new $className;
        $model->controller = strtolower($controller);
        $model->action = $action;
        $model->setView();

        $actionMethod = lcfirst($action) . 'Action';

        if(!method_exists($model, $actionMethod)) {
            throw new \Kuberdock\classes\exceptions\CException('Undefined controller action "'.$action.'"');
        }

        $method = new \ReflectionMethod($model, $actionMethod);
        $method->invoke($model);
    } catch(\Kuberdock\classes\exceptions\CException $e) {
        echo $e;
    }
};

// Catch ajax\stream requests
if(\Kuberdock\classes\Tools::getIsAjaxRequest() || \Kuberdock\classes\Tools::getIsStreamRequest()) {
    $run();
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

$run();

// Footer
$res = $cPanel->api1('Branding', 'include', array('stdfooter.html') );
if($res['cpanelresult']['data']['result']) {
    echo $res['cpanelresult']['data']['result'];
} else {
    echo $cPanel->footer();
}

$cPanel->end();