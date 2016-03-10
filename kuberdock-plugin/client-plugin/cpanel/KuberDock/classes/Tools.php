<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class Tools {
    /**
     * Get param from $_GET variable
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public static function getParam($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Get param from $_POST variable
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public static function getPost($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * @return bool
     */
    public static function getIsAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public static function getIsStreamRequest()
    {
        return isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'stream') !== false;
    }

    /**
     * @param $description
     * @return array
     */
    public static function parseDescription($description)
    {
        $descriptionArr = array();
        $description = explode("\n", $description);

        foreach($description as $row) {
            $tmp = explode(':', $row);
            if(!isset($tmp[1])) continue;
            $descriptionArr[] = trim($tmp[1]);
        }

        return $descriptionArr;
    }

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
     * @return string
     */
    public static function  generatePassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();

        for($i=0; $i<8; $i++) {
            $n = $i == 0 ? rand(0, 25) : rand(0, strlen($alphabet)-1);
            $pass[] = $alphabet[$n];
        }

        return strtolower(implode($pass));
    }
} 