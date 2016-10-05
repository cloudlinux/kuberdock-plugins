<?php


namespace models\billing;


use components\Component;
use models\Model;

class Config extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblconfiguration';

    /**
     * @var Component
     */
    protected static $component;

    /**
     * @var string
     */
    protected $primaryKey = 'setting';

    /**
     * Get config collection
     * @return Component
     */
    public static function get()
    {
        global $CONFIG;

        if (!self::$component) {
            self::$component = new Component();
            if (isset($CONFIG) && $CONFIG) {
                self::$component->setAttributes($CONFIG);
            } else {
                self::$component->setAttributes(Config::all()->toArray());
            }
        }

        return self::$component;
    }

    /**
     *
     * @param string $name
     * @param string $ip
     */
    public static function addAllowedApiIP($name, $ip)
    {
        $ip = current(explode(':', $ip));   // IP with port
        $model = self::find('APIAllowedIPs');
        $data = unserialize($model->value);

        $exist = array_uintersect($data, [$ip], function($e1, $e2) {
            return $e1['ip'] == $e2 ? 0 : 1;
        });

        if (!$exist) {
            $data[] = [
                'ip' => $ip,
                'note' => $name,
            ];
        }

        $model->value = serialize($data);
        $model->save();
    }

    /**
     * @param string $ip
     */
    public static function removeAllowedApiIP($ip)
    {
        $model = self::find('APIAllowedIPs');
        $data = array_filter(unserialize($model->value), function ($e) use ($ip) {
            return ($e['ip'] != $ip);
        });

        $model->value = serialize($data);
        $model->save();
    }
}