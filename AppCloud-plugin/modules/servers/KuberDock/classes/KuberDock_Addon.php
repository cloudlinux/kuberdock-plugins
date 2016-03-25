<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use PDO;
use base\CL_Component;
use base\CL_Query;
use base\models\CL_MailTemplate;
use exceptions\CException;

class KuberDock_Addon extends CL_Component {
    /**
     *
     */
    const REQUIRED_PHP_VERSION = '5.3';
    /**
     *
     */
    const STANDARD_PRODUCT = 'Standard package';

    /**
     *
     */
    const KD_PACKAGE_ID = 0;

    /**
     * Can not be deleted
     */
    const STANDARD_KUBE_TYPE = 0;

    /**
     *
     */
    public function activate()
    {
        if(version_compare(phpversion(), self::REQUIRED_PHP_VERSION) < 0) {
            throw new CException('KuberDock plugin require PHP version' . self::REQUIRED_PHP_VERSION . ' or greater.');
        }

        if(!class_exists('PDO')) {
            throw new CException('KuberDock plugin require PHP (PDO).');
        }

        $db = CL_Query::model();
        $server = KuberDock_Server::model()->getActive();

        if(!$server) {
            throw new CException('Add KuberDock server and server group before activating addon.');
        }

        $group = CL_Query::model()->query('SELECT * FROM `tblproductgroups`
            WHERE name = "KuberDock" ORDER BY `order` ASC LIMIT 1')
            ->getRow();

        if(!$group) {
            $result = CL_Query::model()->query('INSERT INTO `tblproductgroups` (name) VALUES ("KuberDock")');
            $group['id'] = $result->getLastId();
        }

        try {
            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_products` (
                product_id INT,
                kuber_product_id INT,
                UNIQUE KEY (product_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_kubes` (
                id INT AUTO_INCREMENT,
                kuber_kube_id INT,
                product_id INT,
                kuber_product_id INT,
                kube_name VARCHAR(255),
                kube_weight DECIMAL(10,2),
                kube_price DECIMAL(10,2),
                kube_type TINYINT(1) DEFAULT 0,
                cpu_limit DECIMAL(10,4),
                memory_limit INT,
                hdd_limit INT,
                traffic_limit DECIMAL(10,2),
                server_id INT,
                PRIMARY KEY (id),
                INDEX (kuber_kube_id),
                FOREIGN KEY (product_id)
                    REFERENCES KuberDock_products(product_id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_trial` (
                user_id INT,
                service_id INT,
                UNIQUE KEY (user_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_states` (
                id INT AUTO_INCREMENT,
                hosting_id INT NOT NULL,
                product_id INT,
                checkin_date DATE NULL,
                kube_count INT NOT NULL,
                ps_size FLOAT NOT NULL,
                ip_count INT NOT NULL,
                total_sum FLOAT NOT NULL,
                details TEXT,
                PRIMARY KEY (id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_preapps` (
                id INT AUTO_INCREMENT,
                session_id varchar(64) NOT NULL,
                user_id INT NULL,
                product_id INT NULL,
                kuber_product_id INT NULL,
                pod_id VARCHAR(64) DEFAULT NULL,
                data TEXT,
                referer text NULL,
                PRIMARY KEY (id),
                INDEX (session_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_price_changes` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `login` varchar(255) NOT NULL,
                `change_time` datetime NOT NULL,
                `type_id` int(11) NOT NULL,
                `package_id` int(11) NOT NULL,
                `old_value` float DEFAULT NULL,
                `new_value` float DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_items` (
                id INT AUTO_INCREMENT,
                user_id INT NOT NULL,
                app_id INT NOT NULL,
                service_id INT NOT NULL,
                pod_id varchar(64) NOT NULL,
                billable_item_id INT NOT NULL,
                invoice_id INT NOT NULL,
                status VARCHAR(16),
                INDEX (pod_id, app_id),
                PRIMARY KEY (id)
            ) ENGINE=INNODB');

            // Create email templates
            $mailTemplate = CL_MailTemplate::model();
            $mailTemplate->createTemplate($mailTemplate::TRIAL_NOTICE_NAME, 'KuberDock Trial Notice',
                $mailTemplate::TYPE_PRODUCT, 'trial_notice');

            $mailTemplate->createTemplate($mailTemplate::TRIAL_EXPIRED_NAME, 'KuberDock Trial Expired',
                $mailTemplate::TYPE_PRODUCT, 'trial_expired');

            $mailTemplate->createTemplate($mailTemplate::MODULE_CREATE_NAME, 'KuberDock Module Created',
                $mailTemplate::TYPE_PRODUCT, 'module_create');

            $product = new KuberDock_Product();

            if(!KuberDock_Product::model()->loadByAttributes(array(
                'name' => self::STANDARD_PRODUCT,
                'servertype' => KUBERDOCK_MODULE_NAME
            ))) {
                // Create standard product
                $product->setAttributes(array(
                    'gid' => $group['id'],
                    'type' => 'other',
                    'name' => self::STANDARD_PRODUCT,
                    'paytype' => 'free',
                    'autosetup' => 'order',
                    'servertype' => KUBERDOCK_MODULE_NAME,
                    'servergroup' => $server->getGroupId(),
                    //'order' => 1,
                    //'hidden' => '',
                ));


                $api = $server->getApi();
                $package = $api->getPackageById(self::KD_PACKAGE_ID)->getData();
                $kubes = $api->getPackageKubes(self::KD_PACKAGE_ID)->getData();

                $product->setConfigOption('enableTrial', 0);
                $product->setConfigOption('firstDeposit', $package['first_deposit']);
                $product->setConfigOption('priceOverTraffic', $package['price_over_traffic']);
                $product->setConfigOption('pricePersistentStorage', $package['price_pstorage']);
                $product->setConfigOption('priceIP', $package['price_ip']);
                $product->setConfigOption('paymentType', KuberDock_Product::$payment_periods[$package['period']]);
                $product->setConfigOption('billingType', 'Fixed price');

                $product->setConfigOption('debug', 0);

                $product->save();
                $product->createCustomField($product->id, 'Token', $product::FIELD_TYPE_TEXT);

                $db->query('INSERT INTO KuberDock_products VALUES (?, ?)', array($product->id, 0));

                $KuberDock_kubes_sql = "INSERT INTO KuberDock_kubes (
                        `kuber_kube_id`,`kuber_product_id`,`product_id`,`kube_name`,`kube_price`,
                        `kube_type`,`cpu_limit`,
                        `memory_limit`,`hdd_limit`,`traffic_limit`,`server_id`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($kubes as $kube) {
                    $values = array(self::STANDARD_KUBE_TYPE, round($kube['cpu'], 4), $kube['memory'], $kube['disk_space'], 0, $server->id);

                    $db->query($KuberDock_kubes_sql, array_merge(
                            array($kube['id'], NULL, NULL, $kube['name'], NULL),
                            $values
                        )
                    );

                    $db->query($KuberDock_kubes_sql, array_merge(
                            array($kube['id'], self::KD_PACKAGE_ID, $product->id, $kube['name'], $kube['kube_price']),
                            $values
                        )
                    );
                }
            }

            \base\models\CL_Configuration::appendAPIAllowedIPs('KuberDock', $server->ipaddress);

        } catch(Exception $e) {
            $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_products`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_price_changes`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_items`');
            throw $e;
        }
    }

    /**
     *
     */
    public function deactivate()
    {
        $db = CL_Query::model();

        try {
            $productKubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL');
            foreach ($productKubes as $row) {
                if ($row['kuber_product_id'] == 0) continue;

                try {
                    KuberDock_Addon_Kube::model()->loadByParams($row)->deleteKubeFromPackage();
                } catch(Exception $e) {
                    // pass
                }
            }
        } catch(Exception $e) {
            // pass
        }

        try {
            $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NULL');
            foreach($kubes as $row) {
                if($row['kuber_kube_id'] < 3) continue;

                try {
                    KuberDock_Addon_Kube::model()->loadByParams($row)->deleteKube();
                } catch(Exception $e) {
                    // pass
                }
            }
        } catch(Exception $e) {
            // pass
        }

        try {
            $products = KuberDock_Addon_Product::model()->loadByAttributes();
            foreach($products as $row) {
                if($row['kuber_product_id'] == 0) continue;
                try {
                    KuberDock_Addon_Product::model()->loadByParams($row)->deletePackage();
                } catch(Exception $e) {
                    // pass
                }
            }
        } catch(Exception $e) {
            // pass
        }

        $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_products`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_price_changes`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_items`');

        // Delete email templates
        $mailTemplate = CL_MailTemplate::model();
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_NOTICE_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_EXPIRED_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::MODULE_CREATE_NAME, $mailTemplate::TYPE_PRODUCT);

        KuberDock_Product::model()->deleteByAttributes(array('name' => self::STANDARD_PRODUCT, 'servertype' => KUBERDOCK_MODULE_NAME));

        \base\models\CL_Configuration::appendAPIAllowedIPs('KuberDock');
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
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