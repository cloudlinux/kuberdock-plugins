<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Query;
use base\CL_Model;

abstract class CL_Product extends CL_Model {
    /**
     *
     */
    const FIELD_TYPE_TEXT = 'text';
    /**
     *
     */
    const FIELD_TYPE_LINK = 'link';
    /**
     *
     */
    const FIELD_TYPE_PASSWORD = 'password';
    /**
     *
     */
    const FIELD_TYPE_DROPDOWN = 'dropdown';
    /**
     *
     */
    const FIELD_TYPE_TICKBOX = 'tickbox';
    /**
     *
     */
    const FIELD_TYPE_TEXTAREA = 'textarea';

    /**
     * @var
     */
    protected $client;

    /**
     * @return array
     */
    abstract public function getConfig();

    /**
     * @param $serviceId
     * @return mixed
     */
    abstract public function create($serviceId);

    /**
     * @param $serviceId
     * @return mixed
     */
    abstract public function suspend($serviceId);

    /**
     * @param $serviceId
     * @return mixed
     */
    abstract public function unSuspend($serviceId);

    /**
     * @param $serviceId
     * @return mixed
     */
    abstract public function terminate($serviceId);

    /**
     * Init
     */
    public function setTableName()
    {
        $this->tableName = 'tblproducts';
    }

    /**
     * Create custom field
     *
     * @param int $id
     * @param string $fieldName
     * @param string $fieldType (FIELD_TYPE_DROPDOWN, FIELD_TYPE_LINK, FIELD_TYPE_PASSWORD, FIELD_TYPE_TEXT, FIELD_TYPE_TEXTAREA, FIELD_TYPE_TICKBOX)
     * @param string $description
     * @param bool $adminOnly
     * @return mixed
     * @throws Exception
     */
    public function createCustomField($id, $fieldName, $fieldType, $description = '', $adminOnly = true)
    {
        $customField = $this->getCustomField($id, $fieldName);

        if(!$customField) {
            $values = array(
                'type' => 'product',
                'relid' => $id,
                'fieldname' => $fieldName,
                'fieldtype' => $fieldType,
                'description' => $description,
                'adminonly' => $adminOnly ? 'on' : '',
            );
            return insert_query('tblcustomfields', $values);
        } else {
            return false;
        }
    }

    /**
     * Update custom field
     *
     * @param int $productId
     * @param int $serviceId
     * @param string $fieldName
     * @param string $value
     * @return bool
     * @throws Exception
     */
    public function updateCustomField($productId, $serviceId, $fieldName, $value)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $adminuser = $admin['username'];

        $customField = $this->getCustomField($productId, $fieldName);

        if(!$customField) {
            return false;
        }

        $values["serviceid"] = $serviceId;
        $values["customfields"] = base64_encode(serialize(
            array($customField['id'] => $value
        )));

        $result = localAPI('updateclientproduct', $values, $adminuser);

        if($result['result'] != 'success') {
            throw new Exception($result['message']);
        }

        return true;
    }

    /**
     * Delete custom field
     *
     * @param int $id
     * @return bool
     */
    public function deleteCustomField($id)
    {
        return CL_Query::model()->query('DELETE FROM `tblcustomfields` WHERE id = ?', array($id));
    }

    /**
     * Get custom field params
     *
     * @param int $id
     * @param string $fieldName
     * @return $this|void
     */
    public function getCustomField($id, $fieldName)
    {
        return CL_Query::model()
            ->query('SELECT id FROM `tblcustomfields` WHERE relid = ? AND `type`  = ? AND fieldname = ?',
                array ($id, 'product', $fieldName))->getRow();
    }

    /**
     * @param string $name
     * @param string $value
     * @return mixed
     * @throws Exception
     */
    public function setConfigOption($name, $value)
    {
        if(($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            $key += 1;
            $this->{'configoption' . $key} = $value;
        } else {
            throw new Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @param int $index
     * @param string $value
     * @return mixed
     * @throws Exception
     */
    public function setConfigOptionByIndex($index, $value)
    {
        if(array_key_exists(($index-1), array_keys($this->getConfig()))) {
            $this->{'configoption' . $index} = $value;
        } else {
            throw new Exception('Undefined option index: ' . $index);
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getConfigOption($name)
    {
        if(($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            $key += 1;
            if(isset($this->{'configoption' . $key})) {
                return $this->{'configoption' . $key};
            } else {
                throw new Exception('Undefined option: ' . $name);
            }
        } else {
            throw new Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @param string $name
     * @return array($key, $optionParams)
     * @throws Exception
     */
    public function getConfigOptionParams($name)
    {
        $config = $this->getConfig();

        if(($key = array_search($name, array_keys($config))) !== false) {
            $key += 1;
            return array($key, $config[$name]);
        } else {
            throw new Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param CL_Client $client
     * @return $this
     */
    public function setClient(CL_Client $client)
    {
        $this->client = $client;
        return $this;
    }
} 