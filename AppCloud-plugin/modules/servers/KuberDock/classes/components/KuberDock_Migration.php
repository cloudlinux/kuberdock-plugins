<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace components;

use Exception;
use \base\CL_Query;
use \base\CL_Component;
use KuberDock_Addon_Kube;
use KuberDock_Server;
use KuberDock_Product;

class KuberDock_Migration extends CL_Component {
    /**
     * @var CL_Query
     */
    private $_db;

    /**
     * Simple migration
     */
    public function migrate()
    {
        $this->_db = CL_Query::model();

        if(!$this->addonActivated()) {
            return;
        }

        try {
            //$this->addServerIdColumn();
            $this->addUserIdColumn();
            $this->addMigrationTable();
        } catch(Exception $e) {
            // pass
        }
    }

    private function addMigrationTable()
    {
        if ($this->tableExist('KuberDock_migrations')) {
            return;
        }

        $this->_db->query("CREATE TABLE IF NOT EXISTS `KuberDock_migrations` (
            `version` int NOT NULL,
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`version`)
        ) ENGINE=InnoDB;");
    }

    /**
     * Add user_id column to table `KuberDock_preapps`
     */
    public function addUserIdColumn()
    {
        if ($this->fieldExist('KuberDock_preapps', 'user_id')) {
            return;
        }

        $this->_db->query('ALTER TABLE `KuberDock_preapps` ADD COLUMN user_id INT NULL');
    }

    /**
     * Add server_id column to table `KuberDock_products`
     */
    public function addServerIdColumn()
    {
        if($this->fieldExist('KuberDock_kubes', 'server_id')) {
           return;
        }

        $this->_db->query('ALTER TABLE `KuberDock_kubes` ADD COLUMN server_id INT NOT NULL');
        $addonKubes = KuberDock_Addon_Kube::model()->loadByAttributes();

        foreach($addonKubes as $row) {
            if($row['product_id']) {
                $server = KuberDock_Product::model()->loadById($row['product_id'])->getServer();
            } else {
                $server = KuberDock_Server::model()->getActive();
            }

            KuberDock_Addon_Kube::model()->updateById($row['id'], array('server_id' => $server->id));
        }

        // Add standard kube
        $db = CL_Query::model();
        $db->query("INSERT INTO KuberDock_kubes (`kuber_kube_id`, `kuber_product_id`, `product_id`, `kube_name`,
                `kube_price`, `kube_type`, `cpu_limit`, `memory_limit`, `hdd_limit`, `traffic_limit`, `server_id`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array(0, null, null, 'Standard', null, 0, 0.01, 64, 1, 0, KuberDock_Server::model()->getActive()->id));
    }

    public function tableExist($table)
    {
        global $db_name;

        return (bool) $this->_db->query("SHOW TABLES FROM `" . $db_name . "` LIKE '" . $table . "'")->getRow();
    }

    /**
     * @param string $table
     * @param string $field
     * @return bool
     */
    public function fieldExist($table, $field)
    {
        $data = $this->_db->query(sprintf('DESCRIBE `%s`', $table))->getRows();

        foreach($data as $row) {
            if($row['Field'] == $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function addonActivated()
    {
        return $this->_db->query(sprintf("SELECT * FROM `tbladdonmodules` WHERE module='%s'", KUBERDOCK_MODULE_NAME))
            ->getRow();
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