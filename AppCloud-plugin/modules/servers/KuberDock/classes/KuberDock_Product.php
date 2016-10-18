<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Query;
use base\CL_Tools;
use base\models\CL_Currency;
use base\models\CL_Product;
use base\models\CL_Hosting;
use base\models\CL_Client;
use base\models\CL_MailTemplate;
use base\models\CL_BillableItems;
use base\models\CL_Invoice;
use components\KuberDock_Units;
use components\KuberDock_InvoiceItem;
use exceptions\CException;
use exceptions\NotFoundException;

/**
 * Class KuberDock_Product
 */
class KuberDock_Product extends CL_Product {
    const AUTO_SETUP_PAYMENT = 'payment';

    /**
     *
     */
    const UNKNOWN_PAYMENT_PERIOD = 'unknown';

    /**
     * @var array
     */
    public static $payment_periods = array(
        'annual' => 'annually',
        'quarter' => 'quarterly',
        'month' => 'monthly',
        'day' => 'daily',
        'hour' => 'hourly',
    );

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblproducts';
    }

    /**
     * @return array
     */
    public function relations()
    {
        return array(
            'pricing' => array('KuberDock_Pricing', 'relid', array('type' => KuberDock_Pricing::TYPE_PRODUCT)),
        );
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $psUnit = KuberDock_Units::getPSUnits();
//        $trafficUnit = KuberDock_Units::getTrafficUnits(); // AC-3783

        $config = array(
            'enableTrial' => array(
                'Number' => 1, // not used. Just to remember, that list order must not be changed,
                               // in db this options are stored in tblproducts.configoption{Number}
                'FriendlyName' => 'Trial package',
                'Type' => 'yesno',
                'Description' => '&nbsp;',
            ),
            'trialTime' => array(
                'Number' => 2,
                'FriendlyName' => 'User Free Trial period',
                'Type' => 'text',
                'Decimal' => true,
                'Size' => '10',
                'Default' => '0',
                'Description' => 'days',
            ),
            'paymentType' => array(
                'Number' => 3,
                'FriendlyName' => 'Service payment type',
                'Type' => 'dropdown',
                'Options' => implode(',', $this->getPaymentTypes()),
                'Default' => 'monthly',
                'Description' => '',
            ),
            'debug' => array(
                'Number' => 4,
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Logs on "Module Log"',
            ),
            'priceIP' => array(
                'Number' => 5,
                'FriendlyName' => 'Price for IP',
                'Type' => 'text',
                'Decimal' => true,
                'Size' => '10',
                'Default' => '0',
                'Description' => '<span>per IP/hour</span>',
            ),
            'pricePersistentStorage' => array(
                'Number' => 6,
                'FriendlyName' => 'Price for persistent storage',
                'Type' => 'text',
                'Decimal' => true,
                'Size' => '10',
                'Default' => '0',
                'Description' => '<span data-unit="' . $psUnit . '">per ' . $psUnit . '/hour</span>',
            ),
            'priceOverTraffic' => array(
                'Number' => 7,
                'FriendlyName' => ' ',
//            AC-3783
//                'FriendlyName' => 'Price for additional traffic',
//                'Type' => 'text',
//                'Size' => '10',
//                'Default' => '0',
//                'Description' => '<span data-unit="' . $trafficUnit . '">per ' . $trafficUnit . '/hour</span>',
            ),
            'firstDeposit' => array(
                'Number' => 8,
                'FriendlyName' => 'First Deposit',
                'Type' => 'text',
                'Decimal' => true,
                'Size' => '10',
                'Default' => '0',
                'Description' => '',
            ),
            'billingType' => array(
                'Number' => 9,
                'FriendlyName' => 'Billing type',
                'Type' => 'radio',
                'Options' => 'PAYG,Fixed price',
                'Default' => 'Fixed price',
                'Description' => '',
            ),
            'restrictedUser' => array(
                'Number' => 10,
                'FriendlyName' => 'Restricted users',
                'Type' => 'yesno',
                'Description' => '',
            ),
            'trialNoticeEvery' => array(
                'Number' => 11,
                'FriendlyName' => 'Trial period ending notice repeat',
                'Type' => 'text',
                'Decimal' => true,
                'Size' => '10',
                'Default' => '0',
                'Description' => 'days (0 - don\'t send)',
            ),
            'sendTrialExpire' => array(
                'Number' => 12,
                'FriendlyName' => 'Trial period expired notice',
                'Type' => 'yesno',
                'Size' => '10',
                'data-type' => 'trial',
                'Default' => '0',
            ),
        );

        return $config;
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function create($serviceId)
    {
        $service = \KuberDock_Hosting::model()->loadById($serviceId);
        $this->createUser($service);

        // Send module create email
        CL_MailTemplate::model()->sendPreDefinedEmail($serviceId, CL_MailTemplate::MODULE_CREATE_NAME, array(
            'kuberdock_link' => $service->getServer()->getLoginPageLink(),
        ));
    }

    /**
     * @param \KuberDock_Hosting $service
     * @return bool true id user created, false if existed
     *
     * @throws Exception
     */
    public function createUser($service)
    {
        $service->updateById($service->id, array(
            'username' => $service->username ? $service->username : $this->client->email,
            'password' => $service->encryptPassword(substr($this->client->password, 0, 25)),
            'domainstatus' => 'Active',
        ));

        $api = $service->getAdminApi();

        try {
            $api->unDeleteUser($this->client->email);
            $this->update($service->id, true);
        } catch (NotFoundException $e) {
            $api->createUser(array(
                'clientid' => (int) $this->client->id,
                'first_name' => $this->client->firstname,
                'last_name' => $this->client->lastname,
                'username' => $service->username,
                'password' => $service->decryptPassword(),
                'active' => true,
                'suspended' => false,
                'email' => $this->client->email,
                'rolename' => $this->getRole(),
                'package' => $this->getName(),
                'timezone' => 'UTC (+0000)',
            ));
        }

        $service = KuberDock_Hosting::model()->loadById($service->id);
        $token = $service->getApi(true)->getToken();
        $service->updateToken($token);
    }

    /**
     * @param int $serviceId
     * @param bool $activate
     * @return void
     * @throws Exception
     */
    public function update($serviceId, $activate = false)
    {
        $service = \KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $productName = $this->getName();
        $password = $service->decryptPassword();

        $response = $api->getUser($service->username);

        if(!($data = $response->getData()) && !$data) {
            throw new Exception('User not found');
        }

        $attributes = array(
            'package' => $productName,
            'clientid' => (int) $this->client->id,
            'first_name' => $this->client->firstname,
            'last_name' => $this->client->lastname,
            'username' => $service->username,
            'password' => $password,
            'active' => !($service->isTerminated() && !$activate),
            'suspended' => $service->isSuspended(),
            'rolename' => $this->getRole(),
            'timezone' => $data['timezone'],
            'deleted' => 0,
        );

        $api->updateUser($attributes, $data['id']);

        $service->updateById($serviceId, array(
            'username' => $service->username,
            'password' => $service->encryptPassword($password),
        ));

        if(!$service->isTerminated() && !$service->isSuspended()) {
            $service->updateById($serviceId, array(
                'domainstatus' => 'Active',
            ));
        }
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function terminate($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $api->updateUser(array('active' => false, 'suspended' => false), $service->username);
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function suspend($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $api->updateUser(array('suspended' => true), $service->username);
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function unSuspend($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $api->updateUser(array('suspended' => false), $service->username);
    }

    /**
     * Get user products
     *
     * @param int $userId
     * @param mixed $serverId
     * @return array
     */
    public function getByUser($userId, $serverId = null)
    {
        $db = CL_Query::model();
        $params = array(KUBERDOCK_MODULE_NAME, $userId);

        $sql = "SELECT product.*, client.id AS client_id, hosting.id AS hosting_id
            FROM `".$this->tableName."` product
                LEFT JOIN `".CL_Hosting::model()->tableName."` hosting ON hosting.packageid=product.id
                LEFT JOIN `".CL_Client::model()->tableName."` client ON hosting.userid=client.id
            WHERE product.`servertype` = ? AND client.id = ? AND hosting.domainstatus IN ('Active', 'Suspened')";

        if($serverId) {
            $params[] = $serverId;
            $sql .= ' AND hosting.server = ?';
        }

        $products = $db->query($sql, $params)->getRows();

        return $products;
    }

    /**
     * Get all active KuberDock products
     *
     * @return array
     */
    public function getActive()
    {
        $products = array();
        $db = CL_Query::model();

        $sql = "SELECT product.*, kd_product.kuber_product_id
            FROM `".$this->tableName."` product
            INNER JOIN `KuberDock_products` kd_product ON product.id=kd_product.product_id
            WHERE product.`servertype` = ?
            ORDER BY product.name";
        $data = $db->query($sql, array(KUBERDOCK_MODULE_NAME))->getRows();

        foreach($data as $row) {
            $products[$row['id']] = $row;
        }

        return $products;
    }

    /**
     * @return array
     */
    public function getKubes()
    {
        return KuberDock_Addon_Kube_Link::loadByProductId($this->id);
    }

    /**
     *
     */
    public function getDescription()
    {
        $description = array();
        $currency = CL_Currency::model()->getDefaultCurrency();

        if($this->getConfigOption('enableTrial')) {
            $description['Free Trial'] = sprintf('<strong>%s days</strong><br/>',$this->getConfigOption('trialTime'));
        }

        if (0 != $priceIP = (float) $this->getConfigOption('priceIP')) {
            $description['Public IP'] = $this->formatFeature($priceIP, $this->getReadablePaymentType());
        }

        if (0 != $pricePS = (float) $this->getConfigOption('pricePersistentStorage')) {
            $description['Persistent Storage'] = $this->formatFeature($pricePS, '1 ' . KuberDock_Units::getHDDUnits());
        }

//        AC-3783
//        if (0 != $priceOT = (float) $this->getConfigOption('priceOverTraffic')) {
//            $description['Additional Traffic'] = $this->formatFeature($priceOT, '1 ' . KuberDock_Units::getTrafficUnits());
//        }

        foreach($this->getKubes() as $kube) {
            if (!$kube['kube_price']) continue;
            $description['Kube '.$kube['kube_name']] = vsprintf(
                '<strong>%s / %s</strong><br/><em>CPU %s, Memory %s, <br/>Disk Usage %s</em>',
//                '<strong>%s / %s</strong><br/><em>CPU %s, Memory %s, <br/>Disk Usage %s, Traffic %s</em>', // AC-3783
                array(
                    $currency->getFullPrice($kube['kube_price']),
                    $this->getReadablePaymentType(),
                    number_format($kube['cpu_limit'], 2) . ' '.KuberDock_Units::getCPUUnits(),
                    $kube['memory_limit'].' '.KuberDock_Units::getMemoryUnits(),
                    $kube['hdd_limit'].' '.KuberDock_Units::getHDDUnits(),
//                    $kube['traffic_limit'].' '.KuberDock_Units::getTrafficUnits() // AC-3783
                )
            );
        }

        return $description;
    }


    /**
     * @return api\KuberDock_Api
     * @throws Exception
     */
    public function getApi()
    {
        return $this->getServer()->getApi()->setDebugMode($this->getConfigOption('debug'));
    }

    /**
     * @return KuberDock_Server
     * @throws Exception
     */
    public function getServer()
    {
        $serverGroup = KuberDock_ServerGroup::model()->loadById($this->servergroup);

        if($serverGroup) {
            return $serverGroup->getActiveServer();
        } else {
            return KuberDock_Server::model()->getActive();
        }
    }

    /**
     * @return string
     */
    public function getReadablePaymentType()
    {
        $type = $this->getPaymentType();
        $types = array_flip(self::$payment_periods);

        return array_key_exists($type, $types)
            ? $types[$type]
            : self::UNKNOWN_PAYMENT_PERIOD;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return JTransliteration::transliterate($this->name);
    }

    public function formatFeature($value, $units)
    {
        $currency = CL_Currency::model()->getDefaultCurrency();
        $html = $currency->getFullPrice($value) . ' / ' . $units;

        return  sprintf('<strong>%s </strong><br/>', $html);
    }


    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function orderService($userId)
    {
        $result = \base\models\CL_Order::model()->createOrder($userId, $this->id);
        \base\models\CL_Order::model()->acceptOrder($result['orderid'], false);

        $service = \KuberDock_Hosting::model()->loadById($result['productids']);
        $service->createModule();

        return $service;
    }

    /**
     * Add current product to cart
     */
    public function addToCart()
    {
        $_SESSION['cart']['products'] = array();
        $_SESSION['cart']['products'][] = array(
            'pid' => $this->id,
            'domain' => '',
            'billingcycle' => null,
            'configoptions' => null,
            'customfields' => null,
            'addons' => null,
            'server' => null,
        );
    }

    /**
     *
     */
    public function removeFromCart()
    {
        foreach ($_SESSION['cart']['products'] as $k => $row) {
            if ($row['pid'] == $this->id) {
                unset($_SESSION['cart']['products'][$k]);
            }
        }
    }

    /**
     * Add billable item for Pod
     * @param int $userId
     * @param KuberDock_Addon_PredefinedApp $app
     * @param int $invoiceId
     * @return KuberDock_Addon_Items
     * @throws Exception
     */
    public function addBillableApp($userId, KuberDock_Addon_PredefinedApp $app, $invoiceId = null)
    {
        if (!$this->isFixedPrice()) {
            throw new Exception('Fixed price - billable items not needed.');
        }

        $items = $app->getTotalPrice();

        $totalPrice = array_reduce($items, function ($carry, $item) {
            $carry += $item->getTotal();
            return $carry;
        });

        $data = KuberDock_Hosting::model()->getByUser($userId);

        if (!$data) {
            throw new Exception('Service not found');
        }

        $service = KuberDock_Hosting::model()->loadByParams(current($data));

        if ($invoiceId) {
            $invoice = CL_Invoice::model()->loadById($invoiceId);
        }

        // TODO: create billable item even if price 0
        if ($totalPrice == 0) {
            $item = new KuberDock_Addon_Items();
            $item->setAttributes(array(
                'user_id' => $userId,
                'service_id' => $service->id,
                'app_id' => $app->id,
                'pod_id' => $app->getPodId(),
                'invoice_id' => 0,
                'status' => CL_Invoice::STATUS_PAID,
            ));

            return $item;
        }

        list($recur, $recurCycle) = $this->getRecurType();

        $description = $this->getName() . ' - Pod ' . $app->getName();
        $model = CL_BillableItems::model();
        $model->setAttributes(array(
            'userid' => $userId,
            'description' => $description,
            'amount' => $totalPrice,
            'recur' => $recur,
            'recurfor' => 0,
            'recurcycle' => $recurCycle,
            'invoiceaction' => CL_BillableItems::CREATE_RECUR_ID,
        ));

        $model->duedate = CL_Tools::getMySQLFormattedDate($model->getNextDueDate());

        if (isset($invoice)) {
            $invoice_id = $invoice->id;
        } else {
            $client = KuberDock_User::model()->getClientDetails($userId);
            $gateway = $client['client']['defaultgateway']
                ? $client['client']['defaultgateway']
                : $service->paymentmethod;

            $invoice = CL_Invoice::model();
            $invoice_id = $invoice->createInvoice($userId, $items, $gateway);
        }

        $invoiceItem = \base\models\CL_Invoice::model()->loadById($invoice_id);

        if ($invoice->isPayed()) {
            $invoiceItem->status = CL_Invoice::STATUS_PAID;
            $invoiceItem->save();
        }
        $model->invoicecount = 1;
        $model->save();

        $invoiceItems = \base\models\CL_InvoiceItems::model()->loadById($invoiceItem->invoiceitems['id']);
        $invoiceItems->setAttributes(array(
            'type' => $model::TYPE,
            'relid' => $model->id,
        ));
        $invoiceItems->save();

        $status = $invoiceItem->isPayed() ? CL_Invoice::STATUS_PAID : CL_Invoice::STATUS_UNPAID;
        $item = new KuberDock_Addon_Items();
        $item->setAttributes(array(
            'user_id' => $userId,
            'service_id' => $service->id,
            'app_id' => $app->id,
            'pod_id' => $app->getPodId(),
            'billable_item_id' => $model->id,
            'invoice_id' => $invoiceItem->id,
            'status' => $status,
        ));

        $item->save();

        return $item;
    }

    /**
     * @return bool
     */
    public function isKuberProduct()
    {
        return $this->id && $this->servertype == KUBERDOCK_MODULE_NAME;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getPeriodInDays()
    {
        switch($this->getPaymentType()) {
            case 'hourly':
                return 1;
            case 'monthly':
                return 30;
            case 'quarterly':
                return 90;
            case 'annually':
                return 365;
        }
    }

    /**
     * @return array (recur, recurCycle)
     * @throws Exception
     */
    public function getRecurType()
    {
        switch($this->getPaymentType()) {
            case 'hourly':
                throw new Exception('Hourly payment type has no recur type');
            case 'monthly':
                return array(1, CL_BillableItems::CYCLE_MONTH);
            case 'quarterly':
                return array(3, CL_BillableItems::CYCLE_MONTH);
            case 'annually':
                return array(1, CL_BillableItems::CYCLE_YEAR);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isTrial()
    {
        return (bool) $this->getConfigOption('enableTrial');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isFixedPrice()
    {
        return $this->getConfigOption('billingType') == 'Fixed price';
    }

    public function isSetupPayment()
    {
        return $this->autosetup == self::AUTO_SETUP_PAYMENT || !$this->autosetup;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isRestirctedUser()
    {
        return (bool) $this->getConfigOption('restrictedUser');
    }

    /**
     *
     */
    public function hideOrShow()
    {
        if($this->getKubes()) {
            $this->hidden = 0;
        } else {
            $this->hidden = 1;
        }
        $this->save();
    }

    /**
     * @return array
     */
    public static function getPaymentTypes()
    {
        return array(
            'annually',
            'quarterly',
            'monthly',
            'hourly',
        );
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getPaymentType()
    {
        return $this->getConfigOption('paymentType');
    }

    /**
     * @param int $serviceId
     * @param bool $jsRedirect
     */
    public function createPodAndRedirect($serviceId, $jsRedirect = false)
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
        $service = \KuberDock_Hosting::model()->loadById($serviceId);
        if($service->isActive() && $predefinedApp) {
            try {
                if(!($pod = $predefinedApp->isPodExists($service->id))) {
                    $pod = $predefinedApp->create($service->id);
                    $predefinedApp->pod_id = $pod['id'];
                    $predefinedApp->save();
                    $predefinedApp->start($pod['id'], $service->id);
                }
                $this->redirectToPod($service, $pod['id'], $jsRedirect);
            } catch(Exception $e) {
                CException::displayError($e);
            }
        }
    }

    /**
     * @param int $serviceId
     * @param string $podId
     * @param bool $jsRedirect
     */
    public function startPodAndRedirect($serviceId, $podId, $jsRedirect = false)
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $service = \KuberDock_Hosting::model()->loadById($serviceId);

        if ($service->isActive()) {
            try {
                $predefinedApp->payAndStart($podId, $service->id);
                // Mark paid by admin, redirect only user.
                $this->redirectToPod($service, $podId, $jsRedirect);
            } catch (Exception $e) {
                CException::displayError($e);
            }
        }
    }

    /**
     * @param KuberDock_Hosting $service
     * @param string $podId
     * @param bool $jsRedirect
     */
    public function redirectToPod(KuberDock_Hosting $service, $podId, $jsRedirect = false)
    {
        global $whmcs;

        if($whmcs && $whmcs->isAdminAreaRequest()) {
            return;
        }

        $configuration = \base\models\CL_Configuration::model()->get();
        $url = sprintf($configuration->SystemURL .
            '/kdorder.php?a=redirect&sid=%s&podId=%s', $service->id, $podId);

        if ($jsRedirect) {
            $this->jsRedirect($url);
        } else {
            header('Location: ' . $url);
        }
    }

    public function createDefaultKubeIfNeeded()
    {
        $defaultTemplate = KuberDock_Addon_Kube_Template::getDefaultTemplate();

        $defaultKube =  KuberDock_Addon_Kube_Link::model()->loadByAttributes(array(
            'product_id' => $this->id,
            'template_id' => $defaultTemplate['id'],
        ));

        if(!$defaultKube) {
            $addonProduct = KuberDock_Addon_Product::model()->loadById($this->id);
            $kube = KuberDock_Addon_Kube_Link::model()->loadByParams(array(
                'template_id' => $defaultTemplate['id'],
                'product_id' => $this->id,
                'kuber_product_id' => $addonProduct->kuber_product_id,
                'kube_price' => '0.00',
            ));

            $kube->save();

            $this->hideOrShow();
        }
    }

    /**
     * @param string $url
     */
    public function jsRedirect($url)
    {
        global $whmcs;
        // Redirect only user
        if($whmcs && $whmcs->isAdminAreaRequest()) {
            return;
        }

        echo <<<SCRIPT
<script>
    window.location.href = '{$url}';
</script>
SCRIPT;
        exit();
    }

    /**
     * @return string
     */
    public function getRole()
    {
        if($this->isTrial()) {
            return \KuberDock_User::ROLE_TRIAL;
        } elseif($this->isRestirctedUser()) {
            return \KuberDock_User::ROLE_RESTRICTED_USER;
        } else {
            return \KuberDock_User::ROLE_USER;
        }
    }

    /**
     * Return format
     * array(
     *  'cycle' => string
     *  'recurring' => float
     *  'setup' => float
     * )
     * @return array
     */
    public function getPricing()
    {
        if ($this->paytype == 'free') {
            return array(
                'cycle' => 'free',
                'recurring' => -1,
                'setup' => -1,
            );
        }

        $periods = array(
            'monthly',
            'quarterly',
            'semiannually',
            'annually',
            'biennially',
            'triennially',
        );

        $recurring = -1;
        $setup = -1;

        foreach ($periods as $row) {
            if ($this->pricing[$row] != -1) {
                $recurring = $this->pricing[$row];
                $setup = $this->pricing[substr($row, 0, 1) . 'setupfee'];
                break;
            }
        }

        return array(
            'cycle' => ($this->paytype == 'onetime') ? $this->paytype : $row,
            'recurring' => (float) $recurring,
            'setup' => (float) $setup,
        );
    }

    /**
     * @return float
     */
    public function getFirstDeposit()
    {
        return $this->isFixedPrice() ? 0 : (float) $this->getConfigOption('firstDeposit');
    }

    /**
     * For product order invoice upgrade items accordingly to PA
     * @param int $invoiceId
     */
    public function productInvoiceCorrection($invoiceId)
    {
        $service = KuberDock_Hosting::model()->loadByInvoiceId($invoiceId);
        $product = KuberDock_Product::model()->loadById($service->packageid);
        $app = KuberDock_Addon_PredefinedApp::model()->loadBySessionId();

        if ($product && $product->isKuberProduct() && $app) {
            if ($product->isFixedPrice()) {
                $items = $app->getTotalPrice();
            } else {
                $items = array();
            }

            // AC-3839 Add recurring price to invoice
            // Add setup\recurring funds only for newly created service
            $pricing = $product->getPricing();

            if ($pricing['setup'] > 0) {
                $items[] = $product->createInvoice('Setup', $pricing['setup']);
            }

            if ($pricing['recurring'] > 0) {
                $items[] = $product->createInvoice('Recurring ('. $pricing['cycle'] .')', $pricing['recurring']);
            }


            if ($firstDeposit = $product->getFirstDeposit()) {
                $items[] = $product->createInvoice('First deposit', $firstDeposit)->setTaxed(false);
            }

            if (!$items) {
                return;
            }

            $invoice = CL_Invoice::model()->loadById($invoiceId);
            $invoice->updateInvoice($items);

            $invoice = CL_Invoice::model()->loadById($invoiceId);
            $invoiceItems = \base\models\CL_InvoiceItems::model()->loadById($invoice->invoiceitems['id']);
            // In order to system know that it is product order invoice
            $invoiceItems->setAttributes(array(
                'type' => 'Hosting',
                'relid' => $service->id,
            ));
            $invoiceItems->save();
        }
    }

    public function createInvoice($description, $price, $units = null, $qty = 1)
    {
        $invoice = KuberDock_InvoiceItem::create($description, $price, $units, $qty);

        if ($this->tax) {
            $invoice->setTaxed(true);
        }

        return $invoice;
    }

    public function beforeSave()
    {
        foreach ($this->getConfig() as $item) {
            if (isset($item['Decimal']) && $item['Decimal']===true) {
                $value = $this->getConfigOptionByIndex($item['Number']);
                $value = str_replace(',', '.', $value);
                $value = preg_replace('/([^\d\.]+)/i', '', $value);
                $this->setConfigOptionByIndex($item['Number'], $value);
            }
        }

        return true;
    }
} 
