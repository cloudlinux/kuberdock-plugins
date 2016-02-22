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
     * @param string $userName
     * @param string $fieldName
     * @param string $value
     * @return bool
     */
    public function updateCustomField($productId, $serviceId, $userName, $fieldName, $value)
    {
        $customField = $this->getCustomField($productId, $fieldName);

        if(!$customField) {
            return false;
        }

        $values["serviceid"] = $serviceId;
        $values["customfields"] = base64_encode(serialize(
            array($customField['id'] => $value
        )));

        $result = localAPI('updateclientproduct', $values, $userName);

        return ($result['result'] == 'success');
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
     * @param string $name
     * @param mixed $value
     * @param array $attributes
     * @return string
     */
    public function renderConfigOption($name, $value = null, $attributes = array())
    {
        list($key, $params) = $this->getConfigOptionParams($name);
        $value = $value ? $value : (isset($params['Default']) ? $params['Default'] : null);

        if(isset($params['Size'])) {
            $attributes['size'] = $params['Size'];
        }

        $template = '<td class="fieldlabel">'.$params['FriendlyName'].'</td>
            <td class="fieldarea"><label>%s '.$params['Description'].'</label></td>';

        switch($params['Type']) {
            case 'yesno':
                return sprintf($template, $this->renderCheckbox($key, $value, $attributes));
                break;
            case 'dropdown':
                return sprintf($template, $this->renderSelect($key, $params, $value, $attributes));
                break;
            case 'text':
                return sprintf($template, $this->renderText($key, $value, $attributes));
                break;
        }
    }

    /**
     * @param array $key
     * @param bool $value
     * @param array $attributes
     * @return string
     */
    public function renderCheckbox($key, $value = false, $attributes = array())
    {
        $html = sprintf('<input type="checkbox" name="packageconfigoption[%d]"%s%s', $key, $this->getHtmlTags($attributes), $value ? ' checked>' : '>');
        return $html;
    }

    /**
     * @param array $key
     * @param string $value
     * @param array $attributes
     * @return string
     */
    public function renderText($key, $value = '', $attributes = array())
    {
        $html = sprintf('<input type="text" name="packageconfigoption[%d]" value="%s"%s>', $key, $value, $this->getHtmlTags($attributes));
        return $html;
    }

    /**
     * @param array $key
     * @param array $params
     * @param string $value
     * @param array $attributes
     * @return string
     */
    public function renderSelect($key, $params, $value = '', $attributes = array())
    {
        $values = explode(',', $params['Options']);
        $html = sprintf('<select name="packageconfigoption[%d]"%s>', $key, $this->getHtmlTags($attributes));
        foreach($values as $row) {
            $html .= sprintf('<option value="%s"%s>%s</option>', $row, $row == $value ? ' selected' : '', $row);
        }
        $html .= '</select>';

        return $html;
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

    /**
     * Get custom field types
     *
     * @return array
     */
    private function getAvailableFieldTypes()
    {
        return array(
            self::FIELD_TYPE_TEXT,
            self::FIELD_TYPE_LINK,
            self::FIELD_TYPE_PASSWORD,
            self::FIELD_TYPE_DROPDOWN,
            self::FIELD_TYPE_TICKBOX,
            self::FIELD_TYPE_TEXTAREA,
        );
    }

    /**
     * @param array $attributes
     * @return string
     */
    private function getHtmlTags($attributes = array())
    {
        $html = '';

        foreach($attributes as $attribute => $value) {
            $html .= sprintf(' %s="%s"', $attribute, $value);
        }

        return $html;
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
} 