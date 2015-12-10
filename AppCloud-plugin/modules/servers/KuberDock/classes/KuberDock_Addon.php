<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

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
    public function activate()
    {
        if(version_compare(phpversion(), self::REQUIRED_PHP_VERSION) < 0) {
            throw new CException('KuberDock plugin require PHP version' . self::REQUIRED_PHP_VERSION . ' or greater.');
        }

        $db = CL_Query::model();
        $server = KuberDock_Server::model()->getActive();

        if(!$server) {
            throw new CException('Add KuberDock server before activating addon.');
        }

        try {
            $db->query('CREATE TABLE `KuberDock_products` (
                product_id INT,
                kuber_product_id INT,
                UNIQUE KEY (product_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE `KuberDock_kubes` (
                id INT AUTO_INCREMENT,
                kuber_kube_id INT,
                product_id INT,
                kuber_product_id INT,
                kube_name VARCHAR(255),
                kube_weight DECIMAL(10,2),
                kube_price DECIMAL(10,2),
                kube_type TINYINT(1) DEFAULT 0,
                cpu_limit DECIMAL(10,2),
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

            $db->query('CREATE TABLE `KuberDock_trial` (
                user_id INT,
                service_id INT,
                UNIQUE KEY (user_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE `KuberDock_states` (
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

            $db->query('CREATE TABLE `KuberDock_preapps` (
                id INT AUTO_INCREMENT,
                session_id varchar(64) NOT NULL,
                product_id INT NOT NULL,
                kuber_product_id INT NOT NULL,
                data TEXT,
                PRIMARY KEY (id),
                UNIQUE KEY (session_id),
                FOREIGN KEY (product_id)
                    REFERENCES KuberDock_products(product_id)
                    ON UPDATE CASCADE ON DELETE CASCADE
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

            if(!KuberDock_Product::model()->loadByAttributes(array('name' => self::STANDARD_PRODUCT, 'servertype' => KUBERDOCK_MODULE_NAME))) {
                // Create standard product
                $group = CL_Query::model()->query('SELECT * FROM `tblproductgroups` WHERE hidden != 1 ORDER BY `order` ASC LIMIT 1')
                    ->getRow();

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
                $product->setConfigOption('enableTrial', 0);
                $product->setConfigOption('firstDeposit', 0);
                $product->setConfigOption('priceOverTraffic', 0);
                $product->setConfigOption('pricePersistentStorage', 0);
                $product->setConfigOption('priceIP', 0);
                $product->setConfigOption('paymentType', 'hourly');
                $product->setConfigOption('debug', 0);

                $product->save();
                $product->createCustomField($product->id, 'Token', $product::FIELD_TYPE_TEXT);

                $db->query('INSERT INTO KuberDock_products VALUES (?, ?)', array($product->id, 0));

                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(0, NULL, NULL, 'Standard', NULL, 0, 0.01, 64, 1, 0, $server->id));
                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                      array(0, 0, $product->id, 'Standard', 0, 0, 0.01, 64, 1, 0, $server->id));

                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(1, NULL, NULL, 'High CPU', NULL, 0, 0.02, 64, 1, 0, $server->id));
                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(1, 0, $product->id, 'High CPU', 0, 0, 0.02, 64, 1, 0, $server->id));

                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(2, NULL, NULL, 'High memory', NULL, 0, 0.01, 256, 1, 0, $server->id));
                $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                    `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(2, 0, $product->id, 'High memory', 0, 0, 0.01, 256, 1, 0, $server->id));
            }
        } catch(Exception $e) {
            $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_products`');
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

        $products = KuberDock_Addon_Product::model()->loadByAttributes();
        foreach($products as $row) {
            if($row['kuber_product_id'] == 0) continue;

            try {
                KuberDock_Addon_Product::model()->loadByParams($row)->deletePackage();
            } catch(Exception $e) {
                // pass
            }
        }

        $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_products`');

        // Delete email templates
        $mailTemplate = CL_MailTemplate::model();
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_NOTICE_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_EXPIRED_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::MODULE_CREATE_NAME, $mailTemplate::TYPE_PRODUCT);

        KuberDock_Product::model()->deleteByAttributes(array('name' => self::STANDARD_PRODUCT, 'servertype' => KUBERDOCK_MODULE_NAME));
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