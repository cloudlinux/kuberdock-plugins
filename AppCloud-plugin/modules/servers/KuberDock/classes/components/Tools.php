<?php


namespace components;


use DateTime;
use models\billing\Config;

class Tools extends Component
{
    /**
     * Get param from $_GET variable
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function getParam($key, $default = null)
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
    public function getPost($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * @return bool
     */
    public static function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public static function getFormattedDate($date)
    {
        $format = self::getDateFormat();

        if ($date instanceof DateTime) {
            return $date->format($format);
        } else {
            if (strpos($date, '0000') !== false) {
                return null;
            }

            $date = new DateTime($date);
            return $date->format($format);
        }
    }

    /**
     * @return string
     */
    public static function getDateFormat() {
        $config = Config::get();

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
}