<?php


namespace components;


use DateTime;
use models\billing\Config;
use Symfony\Component\Yaml\Yaml;

class Tools extends Component
{
    /**
     * @return bool
     */
    public static function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
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
     * @param string $data
     * @return mixed
     */
    public static function parseYaml($data)
    {
        return Yaml::parse($data);
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
     * @param string $url
     * @param array $data
     */
    public function jsRedirect($url, $data = [])
    {
        if ($data) {
            echo <<<HTML
<html>
    <body onload="redirect()">
        <form id="redirect" method="post" action="{$url}">
HTML;
            foreach ($data as $attribute => $value) {
                echo '<input type="hidden" name="' . $attribute . '" value="' . $value . '">';
            }
            echo <<<HTML
        </form>

        <script>
            function redirect() {
                document.getElementById('redirect').submit();
            }
        </script>
    </body>
</html>
HTML;
        } else {
            echo <<<SCRIPT
<script>
    window.location.href = '{$url}';
</script>
SCRIPT;
        }
        exit();
    }

    /**
     * Temporary ported eloquent method keyBy
     * @param object|array
     * @param string $keyBy
     * @param bool $toArray
     * @return array
     */
    public function keyBy($collection, $keyBy, $toArray = false)
    {
        $results = [];

        foreach ($collection as $item) {
            if (is_object($collection)) {
                $attributes = explode('.', $keyBy);
                if (count($attributes) > 1) {
                    array_walk($attributes, function ($v) use ($item, &$key) {
                        $key = is_object($key) ? $key->{$v} : $item->{$v};
                    });
                } else {
                    $key = $item->{$keyBy};
                }
            } else {
                $key = $item[$keyBy];
            }

            if (is_object($item) && method_exists($item, 'toArray')) {
                $item = $item->toArray();
            }

            $results[$key] = $item;
        }

        return $results;
    }

    /**
     * @param int $length
     * @param string $additional
     * @return string
     */
    public static function generateRandomString($length = 8, $additional = '')
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . $additional;
        $pass = array();

        for ($i=0; $i<$length; $i++) {
            $n = mt_rand(0, strlen($alphabet) - 1);
            $pass[] = $alphabet[$n];
        }

        return strtolower(implode($pass));
    }
}