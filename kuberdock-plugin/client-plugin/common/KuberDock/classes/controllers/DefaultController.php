<?php

namespace Kuberdock\classes\controllers;

use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Base;


class DefaultController extends KuberDock_Controller {
    public function init()
    {
        $this->assets = Base::model()->getStaticPanel()->getAssets();
        $this->assets->registerScripts(array(
            'script/lib/require.min' => array(
                'data-main' => $this->assets->getRelativePath('script/main'),
            ),
        ));

        $this->assets->registerStyles(array(
            'css/bootstrap.min',
            'css/styles',
            'css/xbbcode',
            'script/lib/slider/jquery.nouislider.min',
            'script/lib/owl-carousel/owl.carousel',
            'script/lib/owl-carousel/owl.theme',
        ));
    }

    public function indexAction()
    {
        $panel = Base::model()->getPanel();

        $this->render('index', array(
            'package' => json_encode($panel->billing->getPackage()),
            'packages' => json_encode($panel->billing->getPackages()),
            'maxKubes' => 10,
            'rootURL' => $panel->getApiUrl(),
            'imageRegistryURL' => $panel->getApi()->getRegistryUrl(),
        ));
    }
}
