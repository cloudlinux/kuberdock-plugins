<?php

namespace controllers;

use components\Assets;
use components\BillingApi;
use components\Controller;
use components\Tools;
use Exception;
use exceptions\CException;
use models\addon\App;
use models\billing\Server;


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

    public function restartAction()
    {
        $this->clientArea->requireLogin();
        $podId = Tools::model()->getParam('podId');
        $serviceId = Tools::model()->getParam('serviceId');
        $wipeOut = Tools::model()->getParam('wipeOut', 0);

        if ($wipeOut) {
            $values = [
                'commandOptions' => [
                    'wipeOut' => true
                ],
            ];
        }

        $service = Server::find($serviceId);

        try {
            $service->getAdminApi()->redeployPod($podId, $values);

            $templatePath = '../../modules/servers/KuberDock/view/smarty/restart';
            $this->clientArea->setPageTitle('Add additional kubes');
            $this->clientArea->assign('message', 'Pod restarted');
            $this->clientArea->setTemplate($templatePath);
            $this->clientArea->output();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
