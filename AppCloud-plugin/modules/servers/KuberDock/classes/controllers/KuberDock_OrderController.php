<?php

namespace controllers;

use base\CL_Controller;
use base\CL_Base;
use base\CL_View;
use base\CL_Csrf;
use base\CL_Tools;
use base\models\CL_BillableItems;
use base\models\CL_Currency;
use base\models\CL_Order;
use Exception;
use \exceptions\CException;

class KuberDock_OrderController extends CL_Controller {
    public $action = 'orderApp';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
        $this->assets = new \KuberDock_Assets();
        $this->clientArea = CL_Base::model()->getClientArea();
    }

    public function toCartAction()
    {
        $sessionId = CL_Base::model()->getParam('sessionId');
        $data = \KuberDock_Addon_PredefinedApp::model()->loadByAttributes(array(
            'session_id' => $sessionId,
        ));

        try {
            if (!$data) {
                throw new CException('App not found.');
            }

            $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadByParams(current($data));
            $product = \KuberDock_Product::model()->loadById($predefinedApp->product_id);

            if (!$product) {
                throw new CException('App product not found.');
            }
            $predefinedApp->session_id = \base\CL_Base::model()->getSession();
            $predefinedApp->save();
            $product->addToCart();
            header('Location: cart.php?a=view');
        } catch(Exception $e) {
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function orderAppAction()
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $kdProductId = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD);
        $yaml = html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_YAML_FIELD)), ENT_QUOTES);
        $referer = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_REFERER_FIELD);
        $parsedYaml = \Spyc::YAMLLoadString($yaml);

        try {
            if(isset($parsedYaml['kuberdock']['packageID'])) {
                $kdProductId = $parsedYaml['kuberdock']['packageID'];
            }

            $kdProduct = \KuberDock_Addon_Product::model()->getByKuberId($kdProductId, $referer);
            $product = \KuberDock_Product::model()->loadById($kdProduct->product_id);

            $predefinedApp = $predefinedApp->loadBySessionId();
            if(!$predefinedApp) {
                $predefinedApp = new \KuberDock_Addon_PredefinedApp();
            }

            $predefinedApp->setAttributes(array(
                'session_id' => \base\CL_Base::model()->getSession(),
                'kuber_product_id' => $kdProductId,
                'product_id' => $product->id,
                'data' => $yaml,
                'referer' => $referer,
            ));

            $predefinedApp->save();
            $product->addToCart();
            header('Location: cart.php?a=view');
        } catch(Exception $e) {
            // product not found
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

    public function addKubesAction()
    {
        return;

        $this->clientArea->requireLogin();
        $this->assets->registerScriptFiles(array('jquery.nouislider.all.min'));
        $this->assets->registerStyleFiles(array('jquery.nouislider.min'));

        $podId = CL_Base::model()->getParam('podId');
        $serviceId = CL_Base::model()->getParam('serviceId');
        $errorMessage = '';
        $templatePath = '../../modules/servers/KuberDock/view/smarty/add_kubes';
        $currency = CL_Currency::model()->loadById($this->clientArea->getClient()->currency);

        $service = \KuberDock_Hosting::model()->loadById($serviceId);
        $pod = new \KuberDock_Pod($service);
        $pod->loadById($podId);
        $kube = $pod->getKube();

        if($_POST) {
            try {
                CL_Csrf::check();
                $newKubes = CL_Base::model()->getPost('new_container_kubes', array());
                $values = array();
                foreach($_POST['container_name'] as $k=>$name) {
                    $values[] = array(
                        'name' => $name,
                        'kubes' => $newKubes[$k],
                    );
                }
                $response = $pod->updateKubes($values);
                if(is_numeric($response)) {
                    header('Location: viewinvoice.php?id=' . $response);
                } elseif($response == 'Paid') {
                    header('Location: clientarea.php?action=productdetails&id=' . $serviceId);
                }
                $pod->loadById($podId);
            } catch(Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }

        $this->clientArea->setPageTitle('Add additional kubes');
        $this->clientArea->assign(array(
            'controller' => $this,
            'pod' => $pod,
            'kube' => json_encode($kube),
            'currency' => json_encode($currency->getAttributes()),
            'kubePrice' => $currency->getFullPrice($kube['kube_price']),
            'totalPrice' => $pod->totalPrice(),
            'paymentType' => $pod->getProduct()->getReadablePaymentType(),
            'csrf' => CL_Csrf::render(),
            'errorMessage' => $errorMessage,
        ), '');
        $this->clientArea->setTemplate($templatePath);
        $this->clientArea->output();
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
} 
