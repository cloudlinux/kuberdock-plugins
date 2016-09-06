<?php

namespace components;

use base\CL_Tools;
use base\models\CL_Currency;
use KuberDock_Product;
use KuberDock_Hosting;

class ClientArea extends \base\CL_Component
{
    /* @var CL_Currency */
    private $currency;
    private $values;
    private $products = null;

    public function prepare()
    {
        global $smarty;

        $this->values = $smarty->get_template_vars();
        $this->currency = CL_Currency::model()->getDefaultCurrency();

        // Client area homepage
        if (!isset($this->values['products']) && in_array($this->values['filename'], array('clientarea'))) {
            $this->prepareHomepage();
        }

        // Client cart area
        if (in_array($this->values['filename'], array('cart'))) {
            $this->prepareCartDetails();
            $this->prepareCart();

            if ($this->values['cartitemcount'] == 0) {
                $this->clearPA();
            }
        }

        // Upgrade area
        if (in_array($this->values['filename'], array('upgrade'))) {
            $this->prepareUpgrade();
        }

        $smarty->assign($this->values);

        return $this->values;
    }

    public function prepareHomepage()
    {
        $products = $this->getProducts();
        $services = CL_Tools::getKeyAsField(KuberDock_Hosting::model()->getByUserStatus());

        // Service list
        if (isset($this->values['services'])) {
            foreach ($this->values['services'] as $k=>$service) {
                if ($service['module'] != KUBERDOCK_MODULE_NAME) {
                    continue;
                }

                $productId = $services[$service['id']]['product_id'];
                $product = KuberDock_Product::model()->loadByParams($products[$productId]);
                $serviceModel = KuberDock_Hosting::model()->loadByParams($service);

                $this->values['services'][$k]['billingcycle'] = $product->getReadablePaymentType();
                $this->values['services'][$k]['nextduedate'] = CL_Tools::getFormattedDate(new \DateTime());

                $enableTrial = $product->getConfigOption('enableTrial');
                $trialTime = $product->getConfigOption('trialTime');
                $regDate = \DateTime::createFromFormat(CL_Tools::getDateFormat(), $serviceModel->regdate);

                if ($enableTrial && $serviceModel->isTrialExpired($regDate, $trialTime)) {
                    $this->values['services'][$k]['statustext'] = 'Expired';
                }
            }
        } elseif (isset($products[$this->values['pid']])) {
            $product = KuberDock_Product::model()->loadByParams($products[$this->values['pid']]);

            $enableTrial = $product->getConfigOption('enableTrial');
            $trialTime = $product->getConfigOption('trialTime');
            $regDate = \DateTime::createFromFormat(CL_Tools::getDateFormat(), $this->values['regdate']);

            if ($enableTrial && KuberDock_Hosting::model()->isTrialExpired($regDate, $trialTime)) {
                $this->values['status'] = 'Expired';
            }

            $this->values['firstpaymentamount'] = $this->currency->getFullPrice($product->getConfigOption('firstDeposit'));
            $this->values['billingcycle'] = $product->getReadablePaymentType();
            $this->values['nextduedate'] = CL_Tools::getFormattedDate(new \DateTime());
        }
    }

    /**
     * Client side - Review & Checkout page
     *
     * @throws \Exception
     */
    private function prepareCart()
    {
        if (!isset($this->values['products'])) {
            return;
        }

        $products = $this->getProducts();

        foreach ($this->values['products'] as $k => &$product) {
            if (!isset($products[$product['pid']])) {
                continue;
            }

            $p = KuberDock_Product::model()->loadById($product['pid']);
            if (!$p->isKuberProduct()) {
                continue;
            }

            $product['features'] = $p->getDescription();
            $pricing = $p->getPricing();
            $usersProduct = $this->getUsersProduct();

            if (($predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId()) && isset($product['pricingtext'])) {
                $price = $this->currency->getFullPrice($predefinedApp->getTotalPrice(true));

                if ($usersProduct) {
                    if ($product['pid'] != $usersProduct['packageid']) {
                        $otherProduct = clone(KuberDock_Product::model());
                        $userPackage = $otherProduct->loadById($usersProduct['packageid']);
                        $msg = 'Yaml requires "' . $p->getName()
                            . '" product. You have "' . $userPackage->getName()
                            . '" product. Please upgrade the product.';
                        $p->jsRedirect($predefinedApp->referer . '&error=' . urlencode($msg));
                    }
                } else {
                    if ($pricing['recurring'] > 0) {
                        $price = $this->currency->getFullPrice($pricing['recurring']) . ' + ' . $price;
                    }
                    $product['billingcyclefriendly'] = $p->getReadablePaymentType();
                    if ($pricing['setup'] && $pricing['setup']!=-1) {
                        $product['pricing']['setupfeeonly'] = $this->currency->getFullPrice($pricing['setup']);
                    }
                }

                $product['pricingtext'] = $price . '/' . $p->getReadablePaymentType();
                $product['pricing']['totaltodayexcltax'] = $price;
                $product['pricing']['totalTodayExcludingTaxSetup'] = $price;
                $product['productinfo']['groupname'] = 'Package ' . $predefinedApp->getAppPackageName();
                $product['productinfo']['name'] = ucfirst($predefinedApp->getName());
            }

            if ($depositPrice = $p->getConfigOption('firstDeposit') && !$usersProduct) {
                $product['pricing']['minprice']['price'] = ' First Deposit ' . $this->currency->getFullPrice($depositPrice);

                if (isset($product['pricingtext'])) {
                    $product['pricingtext'] .= ' (  including first deposit: ' . $this->currency->getFullPrice($depositPrice) . ')';
                }
            }
        }
    }

    // Client side - cart product details page
    public function prepareCartDetails()
    {
        $products = $this->getProducts();

        if (isset($this->values['productinfo']) && isset($products[$this->values['productinfo']['pid']])) {
            $product = KuberDock_Product::model()->loadById($this->values['productinfo']['pid']);
            $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
            $kubes = \KuberDock_Addon_Kube_Link::loadByProductId($this->values['productinfo']['pid']);
            $customFields = array();

            if ($predefinedApp) {
                $pricing = $product->getPricing();
                $price = $this->currency->getFullPrice($predefinedApp->getTotalPrice(true));
                $this->values['productinfo']['name'] = ucfirst($predefinedApp->getName());
                $this->values['productinfo']['description'] = 'Package ' . $predefinedApp->getAppPackageName();
                $this->values['pricing'][$pricing['cycle']] .= sprintf(' + %s %s',$price, $this->values['productinfo']['name']);
                $customFields[] = array(
                    'input' => '',
                    'name' => $this->values['productinfo']['name'] . ' Price',
                    'description' => $price . ' / ' . $product->getReadablePaymentType(),
                );
            }

            if ((float) $product->getConfigOption('priceIP')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Public IP',
                    'description' => $this->currency->getFullPrice($product->getConfigOption('priceIP'))
                        . ' / ' . $product->getReadablePaymentType(),
                );
            }

            if ((float) $product->getConfigOption('pricePersistentStorage')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Persistent Storage',
                    'description' => $this->currency->getFullPrice($product->getConfigOption('pricePersistentStorage'))
                        . ' / 1 ' . KuberDock_Units::getHDDUnits(),
                );
            }

            /*AC-3783
            if ((float) $product->getConfigOption('priceOverTraffic')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Additional Traffic',
                    'description' => $this->currency->getFullPrice($product->getConfigOption('priceOverTraffic'))
                        . ' / 1 ' . KuberDock_Units::getTrafficUnits(),
                );
            }*/
            $this->values['customfields'] = array_merge($this->values['customfields'], $customFields);

            $desc = 'Per Kube - %s, CPU - %s, Memory - %s %s, HDD - %s %s';
            //$desc = 'Per Kube - %s, CPU - %s, Memory - %s %s, HDD - %s %s, Traffic - %s %s'; // AC-3783
            $resources = false;

            foreach ($kubes as $kube) {
                if ($predefinedApp) {
                    $kubeType = $predefinedApp->getKubeType();
                    if ($kubeType != $kube['kuber_kube_id']) {
                        continue;
                    }
                    $resources = true;
                }
                $this->values['customfields'][] = array(
                    'input' => '',
                    'name' => $resources ? 'Additional Resources' : 'Kube ' . $kube['kube_name'],
                    'description' => vsprintf($desc, array(
                        $this->currency->getFullPrice($kube['kube_price']) .' / '. $product->getReadablePaymentType(),
                        (float) $kube['cpu_limit'],
                        $kube['memory_limit'],
                        KuberDock_Units::getMemoryUnits(),
                        $kube['hdd_limit'],
                        KuberDock_Units::getHDDUnits(),
                        /* AC-3783
                        $kube['traffic_limit'],
                        KuberDock_Units::getTrafficUnits(),*/
                    )),
                );
            }
        }
    }

    // Client side - product upgrade page
    public function prepareUpgrade()
    {
        if (is_array($this->values['upgradepackages'])) {
            $service = KuberDock_Hosting::model()->loadById($this->values['id']);
            $product = KuberDock_Product::model()->loadById($service->packageid);
            if ($product->isKuberProduct() && $product->getConfigOption('enableTrial')) {
                $service->amount = 0;
                $service->save();
            }

            foreach ($this->values['upgradepackages'] as &$row) {
                $product = KuberDock_Product::model()->loadById($row['pid']);

                if (($firstDeposit = $product->getConfigOption('firstDeposit')) && $product->isKuberProduct()) {
                    $this->values['LANG']['orderfree'] = $this->currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['onetime'] = $this->currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['minprice']['price'] = $this->currency->getFullPrice($firstDeposit);
                }
            }
        } elseif (is_array($this->values['upgrades'])) {
            $upgrade = current($this->values['upgrades']);
            $model = new KuberDock_Product();
            $oldProduct = $model->loadById($upgrade['oldproductid']);
            $model = new KuberDock_Product();
            $newProduct = $model->loadById($upgrade['newproductid']);

            if (!$newProduct || !$oldProduct) {
                return;
            }

            if ($oldProduct->isKuberProduct() && ($firstDeposit = $newProduct->getConfigOption('firstDeposit'))) {
                $this->values['subtotal'] = $this->values['total'] = $this->currency->getFullPrice($firstDeposit);
                if (isset($this->values['upgrades'][0])) {
                    $this->values['upgrades'][0]['price'] = $this->currency->getFullPrice($firstDeposit);
                }
            }
        }
    }

    /**
     * Adds Deposit value to setup fee if needed
     *
     * @param $params
     * @return array|null
     * @throws \Exception
     */
    public function pricingOverride($params)
    {
        global $smarty;

        $this->values = $smarty->get_template_vars();

        $pid = $params['pid'];
        $product = \KuberDock_Product::model()->loadById($pid);

        if (!$product->isKuberProduct()) {
            return null;
        }

        $pricing = $product->getPricing();
        $setupFee = ($pricing['setup'] > 0)
            ? $pricing['setup']
            : 0;

        $recurring = 0;
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();

        if ($predefinedApp && !$predefinedApp->getPod()) {
            if ($this->getUsersProduct()) {
                return array(
                    'setup' => '0.0',
                    'recurring' => $product->isFixedPrice()
                        ? $predefinedApp->getTotalPrice(true)
                        : '0.0',
                );
            }

            // Price for yaml PA
            if ($product->isFixedPrice()) {
                $recurring = $predefinedApp->getTotalPrice(true);
            }
        }

        $recurring = ($pricing['recurring'] > 0)
            ? $pricing['recurring'] + $recurring
            : $recurring;

        return array(
            'setup' => $product->getFirstDeposit() + $setupFee,
            'recurring' => $recurring,
        );
    }

    /**
     * @return array
     */
    private function getProducts()
    {
        if (is_null($this->products)) {
            $this->products = CL_Tools::getKeyAsField(KuberDock_Product::model()->loadByAttributes(array(
                'servertype' => KUBERDOCK_MODULE_NAME,
            )), 'id');
        }

        return $this->products;
    }

    /**
     * Returns user's product, if there is no user, or user has no product - false
     *
     * @return array|bool
     */
    private function getUsersProduct()
    {
        if (!$this->values['loggedin']) {
            return false;
        }

        return current(\KuberDock_Hosting::model()->getByUser($this->values['clientsdetails']['id']));
    }

    /**
     *
     */
    private function clearPA()
    {
        \KuberDock_Addon_PredefinedApp::model()->clear();
    }
}