<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use base\CL_Model;

class CL_AddonModules extends CL_Model
{
    const MODULE_NAME = 'KuberDock';

    public function setTableName()
    {
        $this->tableName = 'tbladdonmodules';
    }

    public static function getSetting($setting)
    {
        $module = self::model()->loadByAttributes(
            array(
                'module' => self::MODULE_NAME,
                'setting' => $setting,
            )
        );

        $module = current($module);

        return isset($module['value'])
            ? $module['value']
            : null;
    }
} 