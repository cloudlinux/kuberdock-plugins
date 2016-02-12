<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace controllers;

use base\CL_Controller;
use base\CL_Base;
use base\CL_Csrf;
use base\CL_Tools;
use base\models\CL_Currency;
use Exception;

class KuberDock_AddonController extends CL_Controller {
    public $action = 'index';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
        $this->assets = new \KuberDock_Assets();
        $this->assets->registerScriptFiles(array('jquery.min', 'bootstrap.min', 'addon'));
        $this->assets->registerStyleFiles(array('bootstrap.min', 'addon'));
    }

    public function indexAction()
    {
        $this->assets->registerScriptFiles(array('jquery.tablesorter.min'));

        /** @var $currency CL_Currency */
        $currency = CL_Currency::model()->getDefaultCurrency();
        $products = $this->getSortedActiveProducts();

        $kubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NULL',
            array('order' => 'kuber_kube_id'));

        $productKubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL');

        $brokenPackages = \KuberDock_Addon_Product::model()->getBrokenPackages();

        foreach ($kubes as &$kube) {
            foreach ($products as $product) {
                $productKube = $this->getProductKube($productKubes, $kube['kuber_kube_id'], $product['id']);

                $kube['packages'][] = array(
                    'name' => $product['name'],
                    'payment_type' => $product['payment_type'],
                    'product_id' => $product['id'],
                    'id' => $productKube ? $productKube['id'] : null,
                    'kube_price' => $productKube ? $productKube['kube_price'] : null,
                );
            }
        }
        unset($kube);

        $this->render('index', array(
            'kubes' => $kubes,
            'products' => $products,
            'brokenPackages' => $brokenPackages,
            'currency' => $currency,
        ));
    }

    private function getProductKube($productKubes, $kube_id, $product_id)
    {
        foreach ($productKubes as $kube) {
            if ($kube['product_id']==$product_id && $kube['kuber_kube_id']==$kube_id) {
                return $kube;
            }
        }

        return null;
    }

    public function addAction()
    {
        $this->assets->registerScriptFiles(array('jquery.form-validator.min'));

        $base = CL_Base::model();
        $kube = \KuberDock_Addon_Kube::model();
        $products = \KuberDock_Product::model()->getActive();
        $servers = \KuberDock_Server::model()->getServers();

        if(!$products) {
            throw new Exception('Products has no relations');
        }

        if($_POST) {
            CL_Csrf::check();
            unset($_POST['csrf_token']);
            unset($_POST['token']);
            $kube->setAttributes($_POST);
            $kube->setAttribute('kube_type', $kube::NON_STANDARD_TYPE);

            try {
                $kube->save();
                $base->redirect($base->baseUrl);
            } catch(Exception $e) {
                $this->error = $e->getMessage();
            }
        }

        $this->render('add', array(
            'products' => $products,
            'kube' => $kube,
            'servers' => $servers,
        ));
    }

    public function deleteAction()
    {
        $id = CL_Base::model()->getParam('id');
        $kube = \KuberDock_Addon_Kube::model()->loadById($id);
        $productKubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL');
        $usedKubes = array_filter($productKubes, function($e) use ($kube) {
            if($e['kuber_kube_id'] == $kube->kuber_kube_id && $e['server_id'] == $kube->server_id) {
                return $e;
            }
        });

        if(!$usedKubes && !$kube->isStandart()) {
            $kube->delete();
            CL_Base::model()->redirect(CL_Base::model()->baseUrl);
        }
    }

    public function kubePriceAction()
    {
        if(!CL_Tools::getIsAjaxRequest() || !$_POST) {
            exit();
        }

        try {
            CL_Csrf::check();

            $kubePrice = CL_Base::model()->getPost('kube_price');
            $id = (int) CL_Base::model()->getPost('id');

            if (!$id) {
//                $product_id = (int) CL_Base::model()->getPost('product_id');
//                $kuber_kube_id = (int) CL_Base::model()->getPost('kuber_kube_id');
//                $patternKube = \KuberDock_Addon_Kube::model()->loadByAttributes(
//                    array('kuber_kube_id' => $kuber_kube_id),
//                    'product_id IS NULL',
//                    array('order' => 'kuber_kube_id')
//                );
//                $patternKube = reset($patternKube);
//                unset($patternKube['id']);
//
//                $kube = \KuberDock_Addon_Kube::model();
//                $addonProduct = \KuberDock_Addon_Product::model()->loadById($product_id);
//                $kube->setAttributes(
//                    array_merge(
//                        $patternKube,
//                        array(
//                            'kube_price' => $kubePrice,
//                            'product_id' => $product_id,
//                            'kuber_product_id' => $addonProduct->kuber_product_id,
//                        )
//                    )
//                );
//                $kube->save();
            }

            $kube = \KuberDock_Addon_Kube::model()->loadById($id);
            if($kube->kube_price != $kubePrice) {
                if ($kubePrice==='') {
//                    $kube->delete();
                } else {
                    $kube->setAttributes(array(
                        'kube_price' => (float) $kubePrice,
                    ));
                    $kube->save();
                }
            }

            echo json_encode(array('error' => false));

        } catch(Exception $e) {
            echo json_encode(array(
                'error' => true,
                'message' => $e->getMessage(),
            ));
        }

        exit();
    }

    public function isKuberProductAction()
    {
        if(CL_Tools::getIsAjaxRequest()) {
            $productId = CL_Base::model()->getParam('productId');
            $serviceId = CL_Base::model()->getParam('serviceId');

            if($productId && $serviceId) {
                $service = \KuberDock_Hosting::model()->loadById($serviceId);

                echo json_encode(array(
                    'kuberdock' => \KuberDock_Product::model()->loadById($productId)->isKuberProduct(),
                    'nextinvoicedate' => CL_Tools::getFormattedDate($service->nextinvoicedate),
                    'nextduedate' => CL_Tools::getFormattedDate($service->nextduedate),
                ));
            } elseif($productId) {
                $product = \KuberDock_Product::model()->loadById($productId);

                echo json_encode(array(
                    'kuberdock' => $product->isKuberProduct(),
                    'trial' => $product->isTrial(),
                ));
            }
        }

        exit();
    }

    /**
     * @return array
     */
    public function getSortedActiveProducts()
    {
        $products = \KuberDock_Product::model()->getActive();

        foreach ($products as &$product) {
            $product['payment_type'] = \KuberDock_Product::model()->loadByParams($products[$product['id']])->getPaymentType();
        }
        unset($product);

        $payment_types = array_flip(\KuberDock_Product::getPaymentTypes());
        uasort($products, function ($a, $b) use ($payment_types) {
            if ($a['payment_type'] == $b['payment_type']) {
                return ($a['id'] > $b['id']) ? 1 : -1;
            }
            return ($payment_types[$a['payment_type']] < $payment_types[$b['payment_type']]) ? 1 : -1;
        });

        return $products;
    }
} 