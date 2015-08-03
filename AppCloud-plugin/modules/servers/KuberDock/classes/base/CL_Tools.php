<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Tools extends CL_Component {

    /**
     * @param $array
     * @param string $field
     * @return array
     */
    public static function getKeyAsField($array, $field = 'id')
    {
        $values = array();

        foreach($array as $arr) {
            if(isset($arr[$field])) {
                $values[$arr[$field]] = $arr;
            }
        }

        return $values;
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public static function getFormattedDate(DateTime $date)
    {
        $format = self::getDateFormat();

        return $date->format($format);
    }

    public static function getDateFormat() {
        $config = CL_Configuration::model()->get();

        switch($config->DateFormat) {
            case 'DD/MM/YYYY':
                $format = 'd/m/Y';
                break;
            case 'DD.MM.YYYY':
                $format = 'd.m.Y';
                break;
            case 'DD-MM-YYYY':
                $format = 'd-m-Y';
                break;
            case 'MM/DD/YYYY':
                $format = 'm/d/Y';
                break;
            case 'YYYY/MM/DD':
                $format = 'Y/m/d';
                break;
            case 'YYYY-MM-DD':
                $format = 'Y-m-d';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }

        return $format;
    }

    /**
     * @return bool
     */
    public static function getIsAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return CL_Component
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