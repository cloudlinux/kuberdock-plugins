<?php


namespace models\billing;


use components\Component;
use models\Model;

class Config extends Model
{
    /**
     * @var bool
     */
    public $incrementing = false;

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
                $collection = Config::all();

                // Since Eloquent 5.3 method list removed, added pluck
                // TODO: when we stop support 6.3
                $data = method_exists($collection, 'list') ?
                    $collection->lists('value','setting') : $collection->pluck('value','setting')->all();
                self::$component->setAttributes($data);
            }
        }

        return self::$component;
    }

    /**
     * suspend' => (bool) Enable Suspension,
     * 'suspendDays' => (int) Suspend Days	,
     * 'termination' => (bool) Enable Termination,
     * 'terminationDays' => (int) Termination Days,
     * 'invoiceReminderDays' => (int) Invoice Unpaid Reminder,
     * 'invoiceNoticeDays' => (int) Invoice Notice,
     * @return array
     */
    public static function getAutomatedSettings()
    {
        $config = self::get();

        return [
            'suspend' => (bool) $config->AutoSuspension,
            'suspendDays' => (int) $config->AutoSuspensionDays,
            'termination' => (bool) $config->AutoTermination,
            'terminationDays' => (int) $config->AutoTerminationDays,
            'invoiceReminderDays' => (int) $config->SendInvoiceReminderDays,
            'invoiceNoticeDays' => (int) $config->CreateInvoiceDaysBefore,
        ];
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

    /**
     * Appends a value to a setting.
     * Setting must be stored in a string, separated with commas
     *
     * @param $setting
     * @param $value
     */
    public static function appendSetting($setting, $value)
    {
        $model = self::find($setting);
        $oldValue = explode(',', $model->value);
        if (!isset(array_flip($oldValue)[$value])) {
            $oldValue[] = $value;
            $model->value = trim(implode(',', $oldValue), ',');
            $model->save();
        }
    }

    /**
     * Removes a value from a setting.
     * Setting must be stored in a string, separated with commas
     *
     * @param $setting
     * @param $value
     */
    public static function removeSetting($setting, $value)
    {
        $model = self::find($setting);
        $oldValue = array_flip(explode(',', $model->value));
        if (isset($oldValue[$value])) {
            unset($oldValue[$value]);
            $model->value = trim(implode(',', array_flip($oldValue)), ',');
            $model->save();
        }
    }
}