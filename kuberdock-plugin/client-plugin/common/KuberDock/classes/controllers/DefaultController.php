<?php

namespace Kuberdock\classes\controllers;

use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Base;


class DefaultController extends KuberDock_Controller {
    public function indexAction()
    {
        $billing = Base::model()->getPanel()->billing;

        $this->render('index', array(
            'package' => json_encode($billing->getPackage()),
            'packages' => json_encode($billing->getPackages()),
            'maxKubes' => 10,
            'rootURL' => 'kuberdock.api.live.php',
            'imageRegistryURL' => Base::model()->getPanel()->getApi()->getRegistryUrl(),
        ));
    }
}
