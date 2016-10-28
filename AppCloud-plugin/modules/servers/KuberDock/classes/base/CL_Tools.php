<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

use DateTime;
use ReflectionClass;
use base\models\CL_Configuration;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CL_Tools
 * @package base
 * @deprecated
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
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d H:i:s');
        }

        if (function_exists('toMySQLDate')) {
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
     * @param string $url
     * @return string
     */
    public static function generateLink($url)
    {
        $config = CL_Configuration::model()->get();

        return $config->SystemURL . '/' . $url;
    }

    /**
     * @param int $length
     * @return string
     */
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

    public static function log($value, $export = true)
    {
        if (!KUBERDOCK_DEBUG) {
            return;
        }

        if ($value instanceof \base\CL_Model) {
            $object = get_class($value);
            $value = $value->getAttributes();
        }

        if ($value instanceof \base\CL_Component) {
            $object = get_class($value);
            $value = $value->getAttributes();
        }

        if ($value instanceof \DateTime) {
            $object = get_class($value);
            $value = $value->format('Y-m-d H:i:s');
        }

        $hl = fopen('/tmp/whmcs.log', 'a');
        ob_start();
        echo PHP_EOL;
        if (isset($object)) {
            echo 'Object ' . $object . ':' . PHP_EOL;
        }
        if ($export) {
            var_export($value);
        } else {
            var_dump($value);
        }
        $content = ob_get_contents();
        ob_end_clean();
        fwrite($hl, $content);
        fclose($hl);
    }

    /**
     * @param string $data
     * @return mixed
     */
    public static function parseYaml($data)
    {
        return Yaml::parse($data);
    }

    /**
     * Convert object to the array.
     * from: https://github.com/ngfw/Recipe
     *
     * @param object $object PHP object
     *
     * @return array
     */
    public static function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return array_map(['self', 'objectToArray'], $object);
    }

    /**
     * Convert array to the object.
     * from: https://github.com/ngfw/Recipe
     *
     * @param array $array PHP array
     *
     * @return object|null
     */
    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();

        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $object->$name = self::arrayToObject($value);
            }
            return $object;
        }

        return null;
    }
} 