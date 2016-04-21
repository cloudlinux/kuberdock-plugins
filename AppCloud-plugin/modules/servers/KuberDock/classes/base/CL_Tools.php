<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

use DateTime;
use ReflectionClass;
use base\models\CL_Configuration;

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
     * @param DateTime|string $date
     * @return string
     */
    public static function getFormattedDate($date)
    {
        $format = self::getDateFormat();

        if($date instanceof DateTime) {
            return $date->format($format);
        } else {
            if(strpos($date, '0000') !== false) {
                return null;
            }

            $date = new DateTime($date);
            return $date->format($format);
        }
    }

    /**
     * @param DateTime | string $date
     * @return string
     */
    public static function getMySQLFormattedDate($date)
    {
        if($date instanceof DateTime) {
            return $date->format('Y-m-d H:i:s');
        }

        if(function_exists('toMySQLDate')) {
            return toMySQLDate($date);
        }

        $date = DateTime::createFromFormat(self::getDateFormat(), $date);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param $date
     * @return DateTime
     */
    public static function sqlDateToDateTime($date)
    {
        if(strpos($date, '0000') !== false) {
            return null;
        }

        return new DateTime($date);
    }

    /**
     * @return string
     */
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
     * Get difference between dates in days
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param string $format (DateInterval format)
     * @return number|string
     */
    public function getIntervalDiff(DateTime $date1, DateTime $date2, $format = '%a')
    {
        $rc = new ReflectionClass($date1);

        if($rc->hasMethod('diff')) {
            return $date2->diff($date1)->format($format);
        } else {
            switch($format) {
                case '%a':       // days
                    $f = 86400;
                    break;
                default:
                    $f = 86400;
                    break;
            }

            return abs(round(($date2->format('U') - $date1->format('U')) / $f));
        }
    }

    /**
     * @param array $vars
     * @return object
     */
    public static function getApiParams($vars) {
        $param = array('action' => array(), 'params' => array());
        $param['action'] = $vars['_POST']['action'];
        unset($vars['_POST']['username']);
        unset($vars['_POST']['password']);
        unset($vars['_POST']['action']);
        $param['params'] = (object) $vars['_POST'];

        return (object) $param;
    }

    /**
     * @param string $url
     * @param string $email
     * @return string
     */
    public static function generateAutoAuthLink($url, $email)
    {
        global $CONFIG;
        global $autoauthkey;

        if (isset($autoauthkey) && $autoauthkey) {
            $loginUrl = $CONFIG['SystemURL'] . '/dologin.php';
            $timestamp = time();
            $hash = sha1($email . $timestamp . $autoauthkey);
            return sprintf('%s?email=%s&timestamp=%s&hash=%s&goto=%s',
                $loginUrl, $email, $timestamp, $hash, urlencode($url));
        } else {
            return $CONFIG['SystemURL'] . '/' . $url;
        }
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

    public static function generateRandomString($length = 8)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pass = array();

        for($i=0; $i<$length; $i++) {
            $n = rand(0, strlen($alphabet)-1);
            $pass[] = $alphabet[$n];
        }

        return strtolower(implode($pass));
    }
} 