<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */


class KuberDock_AddonController extends CL_Controller {
    public $action = 'index';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
        $this->assets = new KuberDock_Assets();
        $this->assets->registerScriptFiles(array('jquery.min', 'bootstrap.min', 'addon'));
        $this->assets->registerStyleFiles(array('bootstrap.min', 'addon'));
    }

    public function indexAction()
    {
        $products = KuberDock_Product::model()->getActive();
        $search = CL_Base::model()->getPost('Search', array());
        $search = array_filter($search);
        $kubes = KuberDock_Addon_Kube::model()->loadByAttributes($search, 'product_id IS NULL', array('order' => 'kube_name'));
        $productKubes = KuberDock_Addon_Kube::model()->loadByAttributes($search, 'product_id IS NOT NULL',
            array('order' => 'product_id'));
        $brokenPackages = KuberDock_Addon_Product::model()->getBrokenPackages();

        $this->render('index', array(
            'productKubes' => $productKubes,
            'kubes' => $kubes,
            'products' => $products,
            'search' => $search,
            'brokenPackages' => $brokenPackages,
        ));
    }

    public function addAction()
    {
        $base = CL_Base::model();
        $kube = KuberDock_Addon_Kube::model();
        $products = KuberDock_Product::model()->getActive();

        if(!$products) {
            throw new Exception('Products has no relations');
        }

        if($_POST) {
            unset($_POST['token']);
            $kube->setAttributes($_POST);
            $kube->setAttribute('kube_type', $kube::NON_STANDART_TYPE);

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
        ));
    }

    public function deleteAction()
    {
        $id = CL_Base::model()->getParam('id');
        $kube = KuberDock_Addon_Kube::model()->loadById($id);
        $productKubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL', array('order' => 'kube_name'));
        $productKubes = CL_Tools::getKeyAsField($productKubes, 'kuber_kube_id');

        if($kube && !isset($productKubes[$kube->id])) {
            $kube->delete();
            CL_Base::model()->redirect(CL_Base::model()->baseUrl);
        }
    }

    public function kubePriceAction()
    {
        if(CL_Tools::getIsAjaxRequest()) {
            try {
                $base = CL_Base::model();
                $productId = $base->getParam('product_id', CL_Base::model()->getPost('product_id'));
                $addonProduct = KuberDock_Addon_Product::model()->loadById($productId);
                $products = KuberDock_Product::model()->getActive();
                $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NULL', array('order' => 'kube_name'));
                $productKubes = KuberDock_Addon_Kube::model()->loadByAttributes(array('product_id' => $productId));

                $kubes = CL_Tools::getKeyAsField($kubes, 'kuber_kube_id');
                $productKubes = CL_Tools::getKeyAsField($productKubes, 'kuber_kube_id');
                $kubes = $productKubes + $kubes;

                if($_POST) {
                    foreach($_POST['id'] as $k=>$id) {
                        $kubePrice = $_POST['kube_price'][$k];
                        $kube = KuberDock_Addon_Kube::model()->loadById($id);
                        $kube->setAttribute('kube_price', $kubePrice);
                        $kube->setAttributes($addonProduct->getAttributes());
                        $kube->save();
                    }

                    echo json_encode(array(
                        'error' => false,
                        'redirect' => $base->baseUrl.'&product_id='.$productId.'#price',
                    ));
                    exit();
                }

                $this->renderPartial('price_form', array(
                    'productId' => $productId,
                    'products' => $products,
                    'priceKubes' => $productId ? $kubes : array(),
                ));
            } catch(Exception $e) {
                echo json_encode(array(
                    'error' => true,
                    'message' => $e->getMessage(),
                ));
            }
        }

        exit();
    }

    public function isKuberProductAction()
    {
        if(CL_Tools::getIsAjaxRequest()) {
            $productId = CL_Base::model()->getParam('productId');

            echo json_encode(array(
                'kuberdock' => KuberDock_Product::model()->loadById($productId)->isKuberProduct(),
            ));
        }

        exit();
    }
} 