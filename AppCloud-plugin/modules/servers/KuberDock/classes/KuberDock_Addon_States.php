<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Addon_States extends CL_Model {
    /**
     *
     */
    public function init()
    {
        $this->_pk = 'id';
    }

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_states';
    }

    /**
     * @param int $hostingId
     * @param DateTime $from
     * @param DateTime $to
     * @return $this|null
     */
    public function getLastState($hostingId, DateTime $from, DateTime $to)
    {
        $from = CL_Tools::getMySQLFormattedDate($from);
        $to = CL_Tools::getMySQLFormattedDate($to);

        $rows = $this->loadByAttributes(array(
            'hosting_id' => $hostingId,
        ), sprintf("checkin_date BETWEEN CAST('%s' AS DATE) AND CAST('%s' AS DATE)", $from, $to),
        array(
            'order' => 'checkin_date DESC',
            'limit' => 1,
        ));

        if(!$rows) {
            return null;
        }

        return $this->loadByParams(current($rows));
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