<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;

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
     * @param int $serviceId
     * @param DateTime $from
     * @param DateTime $to
     * @return $this|null
     */
    public function getLastState($serviceId, DateTime $from, DateTime $to)
    {
        $from = CL_Tools::getMySQLFormattedDate($from);
        $to = CL_Tools::getMySQLFormattedDate($to);

        $rows = $this->loadByAttributes(array(
            'hosting_id' => $serviceId,
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
     * @param int $serviceId
     * @return $this|null
     */
    public function getLastStateByServiceId($serviceId)
    {
        $rows = $this->loadByAttributes(array(
            'hosting_id' => $serviceId,
        ), '', array(
            'order' => 'checkin_date DESC',
            'limit' => 1,
        ));

        if(!$rows) {
            return null;
        }

        return $this->loadByParams(current($rows));
    }
} 