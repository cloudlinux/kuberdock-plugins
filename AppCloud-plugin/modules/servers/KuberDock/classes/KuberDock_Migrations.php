<?php

use base\CL_Model;

class KuberDock_Migrations extends CL_Model
{
    protected $_pk = 'version';

    public function setTableName()
    {
        $this->tableName = 'KuberDock_migrations';
    }

    public static function loadByMin($min = null)
    {
        $condition = $min
            ? ("`version` > " . $min)
            : '';

        return self::model()->loadByAttributes(
            array(),
            $condition,
            array('order' => 'version DESC')
        );
    }

    public static function getLast()
    {
        $migrations = self::model()->loadByAttributes(array(), '', array(
            'order' => 'version DESC',
            'limit' => 1,
        ));

        $migration = current($migrations);

        return $migration['version'];
    }

    public static function addVersion($version)
    {
        self::model()->insert(array('version' => $version));
    }

    public static function removeVersion($version)
    {
        self::model()->loadById($version)->delete();
    }
} 