<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace components;

use Exception;
use \base\CL_Query;
use \base\CL_Component;

/**
 * Class KuberDock_Migration
 * @package components
 * @deprecated
 */
class KuberDock_Migration extends CL_Component
{
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
} 