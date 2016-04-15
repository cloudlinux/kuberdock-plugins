<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use base\CL_Model;

class CL_Configuration extends CL_Model
{
    protected $_pk = 'setting';

    public function setTableName()
    {
        $this->tableName = 'tblconfiguration';
    }

    /**
     * @return $this
     */
    public function get()
    {
        if (!$this->getAttributes()) {
            $values = $this->loadByAttributes();

            foreach ($values as $value) {
                $this->setAttribute($value['setting'], $value['value']);
            }
        }

        return $this;
    }

    /**
     *
     * @param $name
     * @param null|string $ip If $ip not given or is null, deletes the entry
     */
    public static function appendAPIAllowedIPs($name, $ip = null)
    {
        $row = self::model()->loadById('APIAllowedIPs');
        $ips = unserialize($row->value);
        $node_id = self::getNodeOfAPIAllowedIPs($ips, $name);

        if (is_null($ip)) {
            if (!is_null($node_id)) {
                unset($ips[$node_id]);
            }
        } else {
            $node = array(
                'note' => $name,
                'ip' => $ip,
            );

            if (is_null($node_id)) {
                $ips[] = $node;
            } else {
                $ips[$node_id] = $node;
            }
        }

        $row->value = serialize($ips);
        $row->save();
    }

    private static function getNodeOfAPIAllowedIPs($array, $name)
    {
        foreach ($array as $index => $entry) {
            if ($entry['note'] == $name) {
                return $index;
            }
        }

        return null;
    }

    public function beforeSave() {
        $this->updated_at = date('Y-m-d H:i:s');
        return true;
    }
} 