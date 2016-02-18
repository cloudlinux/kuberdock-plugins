<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;
use base\models\CL_Currency;
use exceptions\CException;
use exceptions\ExistException;

class KuberDock_Addon_PriceChange extends CL_Model
{
    public function setTableName()
    {
        $this->tableName = 'KuberDock_price_changes';
    }

    /**
     * @param $type_id
     * @param $package_id
     * @param $old_value
     * @param $new_value
     */
    public static function saveLog($type_id, $package_id, $old_value, $new_value)
    {
        if ($new_value=='') {
            $new_value = null;
        }
        if ($old_value=='') {
            $old_value = null;
        }

        $admin = \base\models\CL_Admin::getCurrentAdmin();
        $logs = self::model()->loadByParams(array(
            'login' => $admin['username'],
            'change_time' => date('Y-m-d H:i:s'),
            'type_id' => $type_id,
            'package_id' => $package_id,
            'old_value' => $old_value,
            'new_value' => $new_value,
        ));
        $logs->save();
    }

    public static function getLogs($limit, $offset)
    {
        $currency = CL_Currency::model()->getDefaultCurrency();
        $logs = self::model()->loadByAttributes(array(), '', array(
            'limit' => $limit,
            'offset' => $offset,
        ));
        foreach ($logs as &$log) {
            $log['time'] = date('H:i Y/m/d', strtotime($log['change_time']));
            $log['description'] = self::getDescription($log, $currency);
        }
        unset($log);

        return $logs;
    }

    private static function getDescription($log, $currency)
    {
        if (is_null($log['old_value'])) {
            return 'Kube type #' . $log['type_id'] . ' added to package #' . $log['package_id']
                . ' with price ' . $currency->getFullPrice($log['new_value']);
        }

        if (is_null($log['new_value'])) {
            return 'Kube type #' . $log['type_id'] . ' with price ' . $currency->getFullPrice($log['old_value'])
                . ' removed from package #' . $log['package_id'];
        }

        return 'Kube type #' . $log['type_id'] . ' price for package #' . $log['package_id']
            . ' changed from ' . $currency->getFullPrice($log['old_value'])
            . ' to ' . $currency->getFullPrice($log['new_value']);
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