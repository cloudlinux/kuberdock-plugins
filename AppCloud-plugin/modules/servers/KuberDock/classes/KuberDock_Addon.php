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
        if (version_compare(phpversion(), self::REQUIRED_PHP_VERSION) < 0) {
            throw new CException('KuberDock plugin require PHP version' . self::REQUIRED_PHP_VERSION . ' or greater.');
        }

        if (!class_exists('PDO')) {
            throw new CException('KuberDock plugin require PHP (PDO).');
        }

        $db = CL_Query::model();
        $server = KuberDock_Server::model()->getActive();

        if (!$server) {
            throw new CException('Add KuberDock server and server group before activating addon.');
        }

        try {
            $server->getApi()->getPackages();
        } catch (Exception $e) {
            throw new CException('Cannot connect to KuberDock server. Please check server credentials.');
        }

        $group = CL_Query::model()->query('SELECT * FROM `tblproductgroups`
            WHERE name = "KuberDock" ORDER BY `order` ASC LIMIT 1')
            ->getRow();

        if (!$group) {
            $result = CL_Query::model()->query('INSERT INTO `tblproductgroups` (name) VALUES ("KuberDock")');
            $group['id'] = $result->getLastId();
        }

        try {
            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_products` (
                product_id INT,
                kuber_product_id INT,
                UNIQUE KEY (product_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_kubes_templates` (
                id INT AUTO_INCREMENT,
                kuber_kube_id INT,
                kube_name VARCHAR(255),
                kube_type TINYINT(1) DEFAULT 0,
                cpu_limit DECIMAL(10,4),
                memory_limit INT,
                hdd_limit INT,
                traffic_limit DECIMAL(10,2),
                server_id INT,
                PRIMARY KEY (id),
                INDEX (kuber_kube_id)
            ) ENGINE=INNODB');

            $db->query('CREATE TABLE IF NOT EXISTS `KuberDock_kubes_links` (
                id INT AUTO_INCREMENT,
                template_id INT,
                product_id INT,
                kuber_product_id INT,
                kube_price DECIMAL(10,2),
                PRIMARY KEY (id),
                FOREIGN KEY (product_id)
                    REFERENCES KuberDock_products(product_id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                FOREIGN KEY (template_id)
                    REFERENCES KuberDock_kubes_templates(id)
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
                PRIMARY KEY (`id`),
                KEY `new_value` (`new_value`)
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

            $db->query("CREATE TABLE `KuberDock_migrations` (
                `version` int NOT NULL,
                `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB;");

            // Add existing migrations
            $migrations = \migrations\Migration::getAvailable('');
            foreach ($migrations as $version) {
                \KuberDock_Migrations::addVersion($version);
            }

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

                $templates = $api->getKubes()->getData();
                foreach ($templates as $template) {
                    $db->query('INSERT INTO KuberDock_kubes_templates VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
                        NULL,
                        $template['id'],
                        $template['name'],
                        \KuberDock_Addon::STANDARD_KUBE_TYPE,
                        round($template['cpu'], 2),
                        $template['memory'],
                        $template['disk_space'],
                        0,
                        $server->id
                    ));
                }

                $templates = \KuberDock_Addon_Kube_Template::model()->loadByAttributes();
                $templates = \base\CL_Tools::getKeyAsField($templates, 'kuber_kube_id');
                $kubes = $api->getPackageKubes(\KuberDock_Addon::KD_PACKAGE_ID)->getData();
                foreach ($kubes as $kube) {
                    $db->query('INSERT INTO KuberDock_kubes_links VALUES (?, ?, ?, ?, ?)', array(
                        NULL,
                        $templates[$kube['id']]['id'],
                        $product->id,
                        \KuberDock_Addon::KD_PACKAGE_ID,
                        $kube['kube_price']
                    ));
                }
            }

            \base\models\CL_Configuration::appendAPIAllowedIPs('KuberDock', $server->ipaddress);

        } catch(Exception $e) {
            $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`'); // todo: remove deprecated
            $db->query('DROP TABLE IF EXISTS `KuberDock_kubes_links`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_kubes_templates`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_products`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_price_changes`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_items`');
            $db->query('DROP TABLE IF EXISTS `KuberDock_migrations`');
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
            $productKubes = KuberDock_Addon_Kube_Link::model()->loadByAttributes(array(), 'kuber_product_id != 0');
            foreach ($productKubes as $row) {
                try {
                    KuberDock_Addon_Kube_Link::model()->loadByParams($row)->deleteKubeFromPackage();
                } catch(Exception $e) {}
            }
        } catch(Exception $e) {}

        try {
            $kubes = KuberDock_Addon_Kube_Template::model()->loadByAttributes(array(), 'kube_type != 0');
            foreach($kubes as $row) {
                try {
                    KuberDock_Addon_Kube_Template::model()->loadByParams($row)->deleteKube();
                } catch(Exception $e) {}
            }
        } catch(Exception $e) {}

        try {
            $products = KuberDock_Addon_Product::model()->loadByAttributes(array(), 'kuber_product_id != 0');
            foreach($products as $row) {
                try {
                    KuberDock_Addon_Product::model()->loadByParams($row)->deletePackage();
                } catch(Exception $e) {}
            }
        } catch(Exception $e) {}

        $db->query('DROP TABLE IF EXISTS `KuberDock_preapps`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_states`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_kubes`'); // todo: remove deprecated
        $db->query('DROP TABLE IF EXISTS `KuberDock_kubes_links`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_kubes_templates`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_trial`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_products`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_price_changes`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_items`');
        $db->query('DROP TABLE IF EXISTS `KuberDock_migrations`');

        // Delete email templates
        $mailTemplate = CL_MailTemplate::model();
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_NOTICE_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::TRIAL_EXPIRED_NAME, $mailTemplate::TYPE_PRODUCT);
        $mailTemplate->deleteTemplate($mailTemplate::MODULE_CREATE_NAME, $mailTemplate::TYPE_PRODUCT);

        KuberDock_Product::model()->deleteByAttributes(array('name' => self::STANDARD_PRODUCT, 'servertype' => KUBERDOCK_MODULE_NAME));

        \base\models\CL_Configuration::appendAPIAllowedIPs('KuberDock');
    }
}