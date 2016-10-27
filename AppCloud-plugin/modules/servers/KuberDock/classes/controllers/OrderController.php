<?php

namespace controllers;

use base\CL_Base;
use components\Assets;
use components\BillingApi;
use components\Controller;
use components\Tools;
use Exception;
use exceptions\CException;
use models\addon\App;


class OrderController extends Controller {
    /**
     * @var string
     */
    public $action = 'orderApp';
    /**
     * @var string
     */
    public $layout = 'addon';
    /**
     * @var object
     */
    protected $billingClientArea;

    /**
     *
     */
    public function init()
    {
        $this->assets = new Assets();
    }

    /**
     * Add predefined app to cart and redirect
     */
    public function toCartAction()
    {
        $id = Tools::model()->getParam('id');

        try {
            $app = App::find($id);

            if (!$app) {
                throw new CException('App not found');
            }

            $app->addToSession();
            BillingApi::model()->addProductToCart($app->product_id);
            header('Location: cart.php?a=view');
        } catch (Exception $e) {
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function redirectAction()
    {
        $this->clientArea->requireLogin();

        $serviceId = \base\CL_Base::model()->getParam('sid');
        $podId = \base\CL_Base::model()->getParam('podId');

        if ($serviceId && $podId) {
            $service = \KuberDock_Hosting::model()->loadById($serviceId);
            $view = new \base\CL_View();
            $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadByPodId($podId);
            $postDescription = htmlentities($predefinedApp->getPostDescription(), ENT_QUOTES);
            try {
                if ($predefinedApp->referer) {
                    header('Location: ' . htmlspecialchars_decode($predefinedApp->referer));
                } else {
                    $link = $service->getServer()->getLoginPageLink();

                    if (USE_JWT_TOKENS) {
                        $link = sprintf('%s/?token2=%s#pods/%s', $link, $service->getApi()->getJWTToken(), $podId);
                        header('Location: ' . $link);
                    } else {
                        $view->renderPartial('client/preapp_complete', array(
                            'token' => $service->getToken(),
                            'action' => $link . '/#pods/' . $podId,
                            'postDescription' => $postDescription ? $postDescription : 'You successfully make payment for application',
                        ));
                    }
                }
                exit;
            } catch (Exception $e) {
                CException::log($e);
                CException::displayError($e);
            }
        }
    }

    public function restartAction()
    {
        $this->clientArea->requireLogin();
        $podId = CL_Base::model()->getParam('podId');
        $serviceId = CL_Base::model()->getParam('serviceId');
        $wipeOut = CL_Base::model()->getParam('wipeOut', 0);

        if($wipeOut) {
            $values = array(
                'commandOptions' => array(
                    'wipeOut' => true),
            );
        }

        $service = \KuberDock_Hosting::model()->loadById($serviceId);
        try {
            $service->getAdminApi()->redeployPod($podId, $values);

            $templatePath = '../../modules/servers/KuberDock/view/smarty/restart';
            $this->clientArea->setPageTitle('Add additional kubes');
            $this->clientArea->assign('message', 'Pod restarted');
            $this->clientArea->setTemplate($templatePath);
            $this->clientArea->output();
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param object $clientArea
     */
    public function setBillingClientArea($clientArea)
    {
        $this->billingClientArea = $clientArea;
    }
}
