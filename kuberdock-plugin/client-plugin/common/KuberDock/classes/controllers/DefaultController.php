<?php

namespace Kuberdock\classes\controllers;

use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Base;


class DefaultController extends KuberDock_Controller {
    public function init()
    {
        $this->assets = Base::model()->getStaticPanel()->getAssets();
        $panel = Base::model()->getPanelType();
        $this->assets->registerScripts(array(
            'script/lib/require.min',
            'script/main',
        ));

        $this->assets->registerStyles(array(
            'css/bootstrap.min',
            'css/' . strtolower($panel) . '/styles',
            'css/xbbcode',
            'script/lib/slider/jquery.nouislider.min',
            'script/lib/owl-carousel/owl.carousel',
            'script/lib/owl-carousel/owl.theme',
        ));
    }

    public function indexAction()
    {
        $panel = Base::model()->getPanel();

        $sysapi = $panel->getAdminApi()->getSysApi('name');
        $maxKubes = $sysapi['max_kubes_per_container']['value'];

        $this->render('index', array(
            'package' => json_encode($panel->billing->getPackage()),
            'packages' => json_encode($panel->billing->getPackages()),
            'packageDefaults' => json_encode($panel->billing->getDefaults()),
            'maxKubes' => $maxKubes,
            'assetsURL' => $panel->getAssets()->getRelativePath(''),
            'rootURL' => $panel->getApiUrl(),
            'imageRegistryURL' => $panel->getApi()->getRegistryUrl(),
            'panelType' => Base::model()->getPanelType(),
            'panelToken' => json_encode($panel->getCSRFToken()),
            'domains' => json_encode($panel->getAdminApi()->getDomains()),
            'setupInfo' => json_encode($panel->getAdminApi()->getSetupInfo()),
        ));
    }

    public function applicationsAction()
    {
        $api = \Kuberdock\classes\Base::model()->getPanel()->getAdminApi();

        $this->render('applications', array(
            'apps' => $api->getClientTemplates(),
        ));
    }
}
