<?php

namespace components;

use Carbon\Carbon;
use models\addon\App;
use models\billing\Client;
use models\billing\Package;
use models\billing\Service;

class ClientArea extends Component 
{
    /**
     * @var array
     */
    private $smartyValues;
    /**
     * @var \models\billing\Currency
     */
    private $currency;

    /**
     * ClientArea constructor.
     */
    public function __construct()
    {
        $client = new Client();
        $this->currency = $client->getSessionCurrency();
    }

    /**
     *
     */
    public function redraw()
    {
        global $smarty;

        $this->smartyValues = $smarty->get_template_vars();

        // Client area homepage
        if (!isset($this->smartyValues['products']) && in_array($this->smartyValues['filename'], array('clientarea'))) {
            $this->prepareHomepage();
        }

        // Client cart area
        if (in_array($this->smartyValues['filename'], array('cart'))) {
            $this->prepareCartDetails();
            $this->prepareCart();

            if ($this->smartyValues['cartitemcount'] == 0) {
                $this->clearPA();
            }
        }

        // Upgrade area
        if (in_array($this->smartyValues['filename'], array('upgrade'))) {
            $this->prepareUpgrade();
        }

        $smarty->assign($this->smartyValues);
    }

    /**
     * Client home page
     */
    public function prepareHomepage()
    {
        /* @var Package $package
         * @var Service $service
         * @var Carbon $expireDate
         */
        if (isset($this->smartyValues['services'])) {
            foreach ($this->smartyValues['services'] as $k => $row) {
                if ($row['module'] != KUBERDOCK_MODULE_NAME) {
                    continue;
                }

                $service = Service::find($row['id']);
                $package = $service->package;

                $this->smartyValues['services'][$k]['billingcycle'] = $package->getReadablePaymentType();
                $this->smartyValues['services'][$k]['nextduedate'] = Tools::getFormattedDate(new \DateTime());

                if ($service->isTrialExpired()) {
                    $this->smartyValues['services'][$k]['statustext'] = 'Expired';
                }
            }
        } elseif (isset($this->smartyValues['pid'])) {
            $package = Package::find($this->smartyValues['pid']);

            if (!$package->isKuberDock()) {
                return;
            }

            $service = Service::find($this->smartyValues['serviceid']);
            $now = new \DateTime();
            $now->setTime(0, 0, 0);

            $expireDate = $service->regdate;
            $expireDate->addDays($package->getTrialTime());

            if ($package->getEnableTrial() && $now >= $expireDate) {
                $this->smartyValues['status'] = 'Expired';
            }

            $this->smartyValues['firstpaymentamount'] = $this->currency->getFullPrice($package->getFirstDeposit());
            $this->smartyValues['billingcycle'] = $package->getReadablePaymentType();
            $this->smartyValues['nextduedate'] = Tools::getFormattedDate($now);
        }
    }

    /**
     * Client side - Review & Checkout page
     *
     * @throws \Exception
     */
    private function prepareCart()
    {
        /* @var Service $service
         * @var Package $package */

        if (!isset($this->smartyValues['products'])) {
            return;
        }

        foreach ($this->smartyValues['products'] as $k => &$product) {
            $package = Package::find($product['pid']);
            
            if (!$package->isKuberDock()) {
                continue;
            }

            $product['features'] = $this->getProductDescription($package);
            $pricing = $package->pricing()->withCurrency($this->currency->id)->first()->getReadable();
            $service = $this->getUserService();

            $app = App::getFromSession();

            if ($app && isset($product['pricingtext'])) {
                $resource = $app->getResource();
                $price = $this->currency->getFullPrice($resource->getPrice(true));

                if ($service) {
                    if ($package->id != $service->packageid) {
                        $msg = 'Yaml requires "' . $package->name
                            . '" product. You have "' . $service->package->name
                            . '" product. Please upgrade the product.';
                        Tools::model()->jsRedirect($app->referer . '&error=' . urlencode($msg));
                    }
                } else {
                    if ($pricing['recurring'] > 0) {
                        $price = $this->currency->getFullPrice($pricing['recurring']) . ' + ' . $price;
                    }
                    $product['billingcyclefriendly'] = $package->getReadablePaymentType();
                    if ($pricing['setup'] && $pricing['setup']!=-1) {
                        $product['pricing']['setupfeeonly'] = $this->currency->getFullPrice($pricing['setup']);
                    }
                }

                $product['pricingtext'] = $price . '/' . $package->getReadablePaymentType();
                $product['pricing']['totaltodayexcltax'] = $price;
                $product['pricing']['totalTodayExcludingTaxSetup'] = $price;
                $product['productinfo']['groupname'] = 'Package ' . $resource->getPackageName();
                $product['productinfo']['name'] = ucfirst($resource->getName());
            }

            if (($depositPrice = $package->getFirstDeposit()) && !$service) {
                $product['pricing']['minprice']['price'] =
                    ' First Deposit ' . $this->currency->getFullPrice($depositPrice);

                if (isset($product['pricingtext'])) {
                    $product['pricingtext'] .=
                        ' (  including first deposit: ' . $this->currency->getFullPrice($depositPrice) . ')';
                }
            }
        }
    }

    /**
     * Client side - cart product details page
     */
    public function prepareCartDetails()
    {
        /* @var Service $service
         * @var Package $package */

        if (isset($this->smartyValues['productinfo'])) {
            $package = Package::find($this->smartyValues['productinfo']['pid']);

            if (!$package->isKuberDock()) {
                return;
            }

            $app = App::getFromSession();
            $kubes = $package->getKubes();
            $customFields = [];

            if ($app) {
                $resource = $app->getResource();
                $pricing = $package->pricing()->withCurrency($this->currency->id)->first()->getReadable();
                $price = $this->currency->getFullPrice($app->getResource()->getPrice());
                $this->smartyValues['productinfo']['name'] = ucfirst($resource->getName());
                $this->smartyValues['productinfo']['description'] = 'Package ' . $resource->getPackageName();
                $this->smartyValues['pricing'][$pricing['cycle']] .=
                    sprintf(' + %s %s', $price, $this->smartyValues['productinfo']['name']);
                $customFields[] = [
                    'input' => '',
                    'name' => $this->smartyValues['productinfo']['name'] . ' Price',
                    'description' => $price . ' / ' . $package->getReadablePaymentType(),
                ];
            }

            if ($package->getPriceIP()) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Public IP',
                    'description' => $this->currency->getFullPrice($package->getPriceIP())
                        . ' / ' . $package->getReadablePaymentType(),
                );
            }

            if ($package->getPricePS()) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Persistent Storage',
                    'description' => $this->currency->getFullPrice($package->getPricePS())
                        . ' / 1 ' . Units::getHDDUnits(),
                );
            }

            /*AC-3783
            if ((float) $product->getConfigOption('priceOverTraffic')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Additional Traffic',
                    'description' => $this->currency->getFullPrice($product->getConfigOption('priceOverTraffic'))
                        . ' / 1 ' . Units::getTrafficUnits(),
                );
            }*/
            $this->smartyValues['customfields'] = array_merge($this->smartyValues['customfields'], $customFields);

            $desc = 'Per Kube - %s, CPU - %s, Memory - %s %s, HDD - %s %s';
            //$desc = 'Per Kube - %s, CPU - %s, Memory - %s %s, HDD - %s %s, Traffic - %s %s'; // AC-3783
            $resources = false;

            foreach ($kubes as $kube) {
                if ($app) {
                    $kubeType = $resource->getKubeType();
                    if ($kubeType != $kube['template']['kuber_kube_id']) {
                        continue;
                    }
                    $resources = true;
                }
                $this->smartyValues['customfields'][] = array(
                    'input' => '',
                    'name' => $resources ? 'Additional Resources' : 'Kube ' . $kube['template']['kube_name'],
                    'description' => vsprintf($desc, [
                        $this->currency->getFullPrice($kube['kube_price']) .' / '. $package->getReadablePaymentType(),
                        (float) $kube['template']['cpu_limit'],
                        $kube['template']['memory_limit'],
                        Units::getMemoryUnits(),
                        $kube['template']['hdd_limit'],
                        Units::getHDDUnits(),
                        /* AC-3783
                        $kube['template']['traffic_limit'],
                        Units::getTrafficUnits(),*/
                    ]),
                );
            }
        }
    }

    /**
     * Client side - product upgrade page
     */
    public function prepareUpgrade()
    {
        /* @var Service $service
         * @var Package $package
         * @var Package $oldProduct
         * @var Package $newProduct
         */
        if (is_array($this->smartyValues['upgradepackages'])) {
            $service = Service::find($this->smartyValues['id']);
            $package = $service->package;

            if ($package->isKuberDock() && $package->getEnableTrial()) {
                $service->amount = 0;
                $service->save();
            }

            foreach ($this->smartyValues['upgradepackages'] as &$row) {
                $package = Package::find($row['pid']);

                if (($firstDeposit = $package->getFirstDeposit()) && $package->isKuberDock()) {
                    $this->smartyValues['LANG']['orderfree'] = $this->currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['onetime'] = $this->currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['minprice']['price'] = $this->currency->getFullPrice($firstDeposit);
                }
            }
        } elseif (is_array($this->smartyValues['upgrades'])) {
            $upgrade = current($this->smartyValues['upgrades']);
            $oldProduct = Package::find($upgrade['oldproductid']);
            $newProduct = Package::find($upgrade['newproductid']);

            if (!$newProduct || !$oldProduct) {
                return;
            }

            if ($oldProduct->isKuberDock() && ($firstDeposit = $newProduct->getFirstDeposit())) {
                $this->smartyValues['subtotal'] = $this->smartyValues['total'] = $this->currency->getFullPrice($firstDeposit);
                if (isset($this->smartyValues['upgrades'][0])) {
                    $this->smartyValues['upgrades'][0]['price'] = $this->currency->getFullPrice($firstDeposit);
                }
            }
        }
    }

    /**
     * Adds Deposit value to setup fee if needed
     *
     * @param int $productId
     * @return array
     * @throws \Exception
     */
    public function productPricingOverride($productId)
    {
        $package = Package::find($productId);
        $client = new Client();
        $currency = $client->getSessionCurrency();

        if (!$package->isKuberDock()) {
            return [];
        }

        $pricing = $package->pricing()->withCurrency($this->currency->id)->first()->getReadable();

        $setupFee = ($pricing['setup'] > 0)
            ? $pricing['setup']
            : 0;

        $recurring = 0;
        $app = App::getFromSession();

        if ($app) {
            // User already has KD service
            if ($this->getUserService()) {
                return [
                    'setup' => 0,
                    'recurring' => $app->getResource()->getPrice(),
                ];
            }

            // Price for yaml PA
            $recurring = $app->getResource()->getPrice();
        }

        $recurring = ($pricing['recurring'] > 0)
            ? $pricing['recurring'] + $recurring
            : $recurring;

        return [
            'setup' => $currency->getRatedPrice($package->getFirstDeposit() + $setupFee),
            'recurring' => $currency->getRatedPrice($recurring),
        ];
    }

    /**
     * @param Package $package
     * @return array
     */
    private function getProductDescription(Package $package)
    {
        $description = array();

        if ($package->getEnableTrial()) {
            $description['Free Trial'] = sprintf('<strong>%s days</strong><br/>', $package->getTrialTime());
        }

        if (0 != $priceIP = $package->getPriceIP()) {
            $description['Public IP'] = $this->formatFeature($priceIP, $package->getPaymentType());
        }

        if (0 != $pricePS = (float) $package->getPricePS()) {
            $description['Persistent Storage'] = $this->formatFeature($pricePS, '1 ' . Units::getHDDUnits());
        }

        /*AC-3783
        if (0 != $priceOT = (float) $package->getConfigOption('priceOverTraffic')) {
            $description['Additional Traffic'] = $package->formatFeature($priceOT, '1 ' . Units::getTrafficUnits());
        }*/

        foreach ($package->kubePrice as $kubePrice) {
            if (!$kubePrice['kube_price']) {
                continue;
            }

            $template = $kubePrice->template;
            $description['Kube ' . $template['kube_name']] = vsprintf(
                '<strong>%s / %s</strong><br/><em>CPU %s, Memory %s, <br/>Disk Usage %s</em>',
                [
                    $this->currency->getFullPrice($kubePrice['kube_price']),
                    $package->getPaymentType(),
                    number_format($template['cpu_limit'], 2) . ' ' . Units::getCPUUnits(),
                    $template['memory_limit'] . ' ' . Units::getMemoryUnits(),
                    $template['hdd_limit'] . ' ' . Units::getHDDUnits(),
                    //$kube['traffic_limit'].' '.Units::getTrafficUnits() // AC-3783
                ]
            );
        }

        return $description;
    }

    /**
     * @param float $value
     * @param string $units
     * @return string
     */
    private function formatFeature($value, $units)
    {
        return  sprintf('<strong>%s / %s</strong><br/>', $this->currency->getFullPrice($value), $units);
    }

    /**
     * Return user's service
     *
     * @return Service|null
     */
    private function getUserService()
    {
        if (!$this->smartyValues['loggedin']) {
            return null;
        }

        return Service::typeKuberDock()->where('userid', $this->smartyValues['clientsdetails']['id'])->first();
    }

    /**
     *
     */
    private function clearPA()
    {
        $app = new App();
        $app->deleteFromSession();
    }
}