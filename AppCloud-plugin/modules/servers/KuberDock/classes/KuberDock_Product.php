<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Product extends CL_Product {

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
        $config = array(
            'paymentType' => array(
                'FriendlyName' => 'Service payment type',
                'Type' => 'dropdown',
                'Options' => implode(',', $this->getPaymentType()),
                'Default' => 'hourly',
                'Description' => '',
            ),
            'debug' => array(
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Description' => 'Logs on "Module Log"',
            ),
            'trialTime' => array(
                'FriendlyName' => 'User Free Trial period',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'Days',
            ),
            'priceIP' => array(
                'FriendlyName' => 'Price for IP',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'per hour',
            ),
            'pricePersistentStorage' => array(
                'FriendlyName' => 'Price for persistent storage',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'per '.KuberDock_Units::getPSUnits(),
            ),
            'priceOverTraffic' => array(
                'FriendlyName' => 'Price for additional traffic',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'per '.KuberDock_Units::getTrafficUnits(),
            ),
            'showInCpanel' => array(
                'FriendlyName' => 'Use in cPanel',
                'Type' => 'yesno',
                'Description' => '',
            ),
            'firstDeposit' => array(
                'FriendlyName' => 'First Deposit',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => '',
            ),
            'enableTrial' => array(
                'FriendlyName' => 'Trial package',
                'Type' => 'yesno',
                'Default' => '',
                'Description' => '',
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
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $productName = $this->getName();
        $service->username = $this->client->email;
        $password = $service->decryptPassword();
        $role = $this->getConfigOption('trialTime') ? KuberDock_User::ROLE_TRIAL : KuberDock_User::ROLE_USER;

        try {
            $response = $api->getUser($service->username);

            if(!($data = $response->getData()) && !$data) {
                throw new UserNotFoundException('User not found');
            }

            $this->update($serviceId);
        } catch(UserNotFoundException $e) {
            $response = $api->createUser(array(
                'first_name' => $this->client->firstname,
                'last_name' => $this->client->lastname,
                'username' => $service->username,
                'password' => $password,
                'active' => 1,
                'suspended' => 0,
                'email' => $this->client->email,
                'rolename' => $role,
                'package' => $productName,
                'password_hash' => '',
            ));

            $token = $service->setAttributes(array(
                'username' => $service->username,
                'password' => $service->encryptPassword($password),
            ))->getApi()->getToken();
            $service->updateToken($token);
            $created = true;
        }

        $service->updateById($serviceId, array(
            'username' => $service->username,
            'password' => $service->encryptPassword($password),
            'domainstatus' => 'Active',
        ));

        if(isset($created) && $created) {
            // Send module create email
            CL_MailTemplate::model()->sendPreDefinedEmail($serviceId, CL_MailTemplate::MODULE_CREATE_NAME, array(
                'kuberdock_link' => $service->getServer()->getLoginPageLink(),
            ));
        }
    }

    /**
     * @param int $serviceId
     * @return void
     * @throws Exception
     */
    public function update($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $productName = $this->getName();
        $service->username = $this->client->email;
        $password = $service->decryptPassword();
        $role = $this->getConfigOption('trialTime') ? KuberDock_User::ROLE_TRIAL : KuberDock_User::ROLE_USER;

        $response = $api->getUser($service->username);

        if(!($data = $response->getData()) && !$data) {
            throw new Exception('User not found');
        }

        $api->updateUser(array(
            'active' => 1,
            'package' => $productName,
            'first_name' => $this->client->firstname,
            'last_name' => $this->client->lastname,
            'username' => $service->username,
            'password' => $password,
            'active' => 1,
            'suspended' => 0,
            'email' => $this->client->email,
            'rolename' => $role,
            'package' => $productName,
        ), $service->username);

        $service->updateById($serviceId, array(
            'username' => $service->username,
            'password' => $service->encryptPassword($password),
            'domainstatus' => 'Active',
        ));

        if(!$token = $service->getToken()) {
            $token = $service->getApi()->getToken();
            $service->updateToken($token);
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
        $api->updateUser(array('active' => 0), $service->username);
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function suspend($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $api->updateUser(array('suspended' => 1), $service->username);
    }

    /**
     * @param int $serviceId
     * @return void
     */
    public function unSuspend($serviceId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getAdminApi();
        $api->updateUser(array('suspended' => 0), $service->username);
    }

    /**
     * Get active user products
     *
     * @param int|null $userId
     * @return array
     */
    public function getUserActive($userId = null)
    {
        $db = CL_Query::model();
        $params = array('Active');

        $sql = "SELECT product.*, client.id AS client_id, hosting.id AS hosting_id
            FROM `".$this->tableName."` product
                LEFT JOIN `".CL_Hosting::model()->tableName."` hosting ON hosting.packageid=product.id
                LEFT JOIN `".CL_Client::model()->tableName."` client ON hosting.userid=client.id
            WHERE client.status = ?";

        if($userId) {
            $sql .= ' AND client.id = ?';
            array_push($params, array($userId));
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

        $sql = "SELECT product.* FROM `".$this->tableName."` product
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
        return KuberDock_Addon_Kube::model()->loadByAttributes(array(
            'product_id' => $this->id,
        ));
    }

    /**
     *
     */
    public function setDescription()
    {
        $view = new CL_View();
        $id = $this->pid ? $this->pid : $this->id;
        $currency = CL_Currency::model()->getDefaultCurrency();
        $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(
            'product_id' => $id,
        ));

        $description = $view->renderPartial('client/order_info', array(
            'currency' => $currency,
            'product' => $this,
            'kubes' => $kubes,
        ), false);

        $this->updateById($id, array('description' => $description));
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        $serverGroup = KuberDock_ServerGroup::model()->loadById($this->servergroup);
        if($serverGroup) {
            $api = $serverGroup->getActiveServer()->getApi()->setDebugMode($this->getConfigOption('debug'));
        } else {
            $api = KuberDock_Server::model()->getActive()->getApi()->setDebugMode($this->getConfigOption('debug'));
        }
        return $api;
    }

    /**
     * @return string
     */
    public function getReadablePaymentType()
    {
        switch($this->getConfigOption('paymentType')) {
            case 'monthly':
                return 'month';
            case 'daily':
                return 'day';
            case 'hourly':
                return 'hour';
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        $tr = JTransliteration::transliterate($this->name);

        return $tr == KuberDock_Addon::STANDARD_PRODUCT ? 'basic' : $tr;
    }

    /**
     * @return string
     */
    public function getReadablePersistentStorage()
    {
        $currency = CL_Currency::model()->getDefaultCurrency();

        return $currency->getFullPrice($this->getConfigOption('pricePersistentStorage'))
            .' / 1 '.KuberDock_Units::getPSUnits();
    }

    /**
     * @return string
     */
    public function getReadableOverTraffic()
    {
        $currency = CL_Currency::model()->getDefaultCurrency();

        return $currency->getFullPrice($this->getConfigOption('priceOverTraffic'))
            .' / 1 '.KuberDock_Units::getTrafficUnits();
    }

    /**
     * @return bool
     */
    public function isKuberProduct()
    {
        return $this->servertype == KUBERDOCK_MODULE_NAME;
    }

    /**
     * @return array
     */
    private function getPaymentType()
    {
        return array(
            'monthly',
            'daily',
            'hourly',
        );
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return KuberDock_Product
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 