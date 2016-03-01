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

    public function orderAppAction()
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $kdProductId = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD);
        $yaml = html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_YAML_FIELD)), ENT_QUOTES);
        $referer = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_REFERER_FIELD);
        $parsedYaml = \Spyc::YAMLLoadString($yaml);
        $userId = $_SESSION['uid'];

        try {
            if(isset($parsedYaml['kuberdock']['packageID'])) {
                $kdProductId = $parsedYaml['kuberdock']['packageID'];
            }

            if(!$referer) {
                if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
                    $referer = $_SERVER['HTTP_REFERER'];
                } elseif(isset($parsedYaml['kuberdock']['server'])) {
                    $referer = $parsedYaml['kuberdock']['server'];
                } elseif($server = \KuberDock_Server::model()->getActive()) {
                    $referer = $server->getApiServerUrl();
                } else {
                    throw new CException('Cannot get KuberDock server url');
                }
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
            ));

            $predefinedApp->save();

            $this->clientArea->requireLogin();

            $data = \KuberDock_Hosting::model()->getByUser($userId, $referer);
            $service = \KuberDock_Hosting::model()->loadByParams(current($data));

            if($product->isFixedPrice()) {
                if(!$service) {
                    $result = CL_Order::model()->createOrder($userId, $product->id);
                    CL_Order::model()->acceptOrder($result['orderid']);
                    $service = \KuberDock_Hosting::model()->loadById($result['productids']);
                }
                $item = $product->addBillableApp($userId, $predefinedApp);
                if(!($pod = $predefinedApp->isPodExists($service->id))) {
                    $pod = $predefinedApp->create($service->id, 'unpaid');
                }
                $predefinedApp->pod_id = $pod['id'];
                $predefinedApp->save();
                $item->pod_id = $pod['id'];
                $item->save();

                if($item->isPayed()) {
                    $product->startPodAndRedirect($item->service_id, $item->pod_id);
                } else {
                    header('Location: viewinvoice.php?id=' . $item->invoice_id);
                }
            } else {
                if(!$service) {
                    $result = CL_Order::model()->createOrder($userId, $product->id);
                    CL_Order::model()->acceptOrder($result['orderid']);
                    \KuberDock_Hosting::model()->loadById($result['productids']);
                    $product->createPodAndRedirect($service->id);
                } else {
                    $product->createPodAndRedirect($service->id);
                }
            }
        } catch(Exception $e) {
            // product not found
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function orderPodAction()
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $pod = json_decode(html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_POD_FIELD)), ENT_QUOTES));
        $user = json_decode(html_entity_decode(urldecode(CL_Base::model()->getParam('user')), ENT_QUOTES));
        $referer = CL_Base::model()->getParam('referer', '');
        $userId = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;

        try {
            $predefinedApp = $predefinedApp->loadBySessionId($pod->id);
            if(!$predefinedApp) {
                $predefinedApp = new \KuberDock_Addon_PredefinedApp();
            }
            $predefinedApp->referer = htmlspecialchars_decode($referer);

            if(!isset($user->package_id)) {
                throw new Exception('User has no package');
            }

            $data = \KuberDock_Addon_Product::model()->loadByAttributes(array(
                'kuber_product_id' => $user->package_id,
            ));
            $addonProduct = \KuberDock_Addon_Product::model()->loadByParams(current($data));
            $product = \KuberDock_Product::model()->loadById($addonProduct->product_id);
            $predefinedApp->setAttributes(array(
                'session_id' => \base\CL_Base::model()->getSession(),
                'data' => json_encode($pod),
                'pod_id' => $pod->id,
                'kuber_product_id' => $addonProduct->kuber_product_id,
                'product_id' => $product->id,
            ));
            $predefinedApp->save();

            if(!isset($product->id)) {
                throw new Exception('Product not found');
            }

            $this->clientArea->requireLogin();

            $data = \KuberDock_Addon_Items::model()->loadByAttributes(array(
                'pod_id' => $pod->id,
            ));

            if(!$data) {
                $item = $product->addBillableApp($userId, $predefinedApp);
            } else {
                $item = \KuberDock_Addon_Items::model()->loadByParams(current($data));
            }

            if($item->isPayed()) {
                $product->startPodAndRedirect($item->service_id, $item->pod_id);
            } else {
                header('Location: viewinvoice.php?id=' . $item->invoice_id);
            }
        } catch(Exception $e) {
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function redirectAction()
    {
        $this->clientArea->requireLogin();

        $serviceId = \base\CL_Base::model()->getParam('sid');
        $podId = \base\CL_Base::model()->getParam('podId');

        if($serviceId && $podId) {
            $service = \KuberDock_Hosting::model()->loadById($serviceId);
            $view = new \base\CL_View();
            $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId($podId);
            $postDescription = htmlentities($predefinedApp->getPostDescription(), ENT_QUOTES);
            try {
                $pod = $service->getApi()->getPod($podId);
                if($predefinedApp->referer) {
                    header('Location: '. htmlspecialchars_decode($predefinedApp->referer));
                } else {
                    $view->renderPartial('client/preapp_complete', array(
                        'serverLink' => $service->getServer()->getLoginPageLink(),
                        'token' => $service->getToken(),
                        'podId' => $podId,
                        'postDescription' => $postDescription ? $postDescription : 'You successfully make payment for application',
                    ));
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
            $service->getApi()->redeployPod($podId, $values);

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
