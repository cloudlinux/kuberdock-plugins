<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Model {
    const ACTION_INSERT = 'insert';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * @var string
     */
    protected $tableName = '';
    /**
     * @var string
     */
    protected $action;
    /**
     * @var CL_Query
     */
    protected $_db;
    /**
     * Primary key
     * @var string
     */
    protected $_pk = 'id';
    /**
     * @var array
     */
    protected $_values = array();
    /**
     * @var array
     */
    protected static $_models = array();

    /**
     *
     */
    public function __construct()
    {
        $this->_db = CL_Query::model();
        $this->setTableName();
        $this->initAttributes();
        $this->init();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_values[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        if(isset($this->_values[$name])) {
            unset($this->_values[$name]);
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        if(isset($this->_values[$name])) {
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function initAttributes()
    {
    }

    /**
     *
     */
    public function init()
    {
    }

    /**
     * @param $id
     * @return $this
     */
    public function loadById($id)
    {
        clone($this);
        $this->action = self::ACTION_UPDATE;
        $sql = sprintf('SELECT * FROM `%s`  WHERE `%s` = ?', $this->tableName, $this->_pk);
        $row = $this->_db->query($sql, array($id))->getRow();

        if(!$row) {
            return false;
        }

        $this->setAttributes($row);

        foreach($this->relations() as $attribute => $row) {
            $model = $this->model($row[0]);
            $attributes = array($row[1] => $id);
            if(isset($row[2]) && is_array($row[2])) {
                $attributes = array_merge($attributes, $row[2]);
            }
            $rows = $model->loadByAttributes($attributes);
            if($rows) {
                $this->setAttribute($attribute, current($rows));
            }
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @param string $condition
     * @param array $params
     * @return array
     */
    public function loadByAttributes($attributes = array(), $condition = '', $params = array())
    {
        $where = array();
        $values = array_values($attributes);

        foreach($attributes as $attribute=>$value) {
            $where[] = "`$attribute` = ?";
        }

        $sql = "SELECT * FROM `".$this->tableName."`";
        $sql .= ($where || $condition) ? ' WHERE' : '';
        $sql .= $where ? implode(' AND ', $where) : '';

        if($condition) {
            $sql .= $attributes ? ' AND' : '';
            $sql .= $condition ? ' '.$condition : '';
        }

        foreach($params as $attr => $value) {
            switch($attr) {
                case 'limit':
                    $sql .= sprintf(' LIMIT %s', $value);
                    break;
                case 'order':
                    if(is_array($value)) {
                        $order = array();
                        foreach($value as $attr => $value) {
                            $order[] = sprintf('`%s` %s', $attr, $value);
                        }

                        if($order) {
                            $sql .= ' ORDER BY ' . implode(', ', $order);
                        }
                    } else {
                        $sql .= sprintf(' ORDER BY %s', $value);
                    }
                    break;
                case 'group':
                    if(is_array($value)) {
                        $order = array();
                        foreach($value as $attr => $value) {
                            $order[] = sprintf('`%s` %s', $attr, $value);
                        }

                        if($order) {
                            $sql .= ' GROUP BY ' . implode(', ', $order);
                        }
                    } else {
                        $sql .= sprintf(' GROUP BY %s', $value);
                    }
                    break;
            }

        }

        $rows = $this->_db->query($sql, $values)->getRows();

        return $rows;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function loadByParams($params = array())
    {
        $this->_values = $params;

        return $this;
    }

    /**
     * @param array $values
     * @return CL_Query
     */
    public function insert($values = array())
    {
        $this->action = self::ACTION_INSERT;
        $values = empty($values) ? $this->getAttributes() : $values;

        foreach($values as $field=>$value) {
            $fields[] = '?';
        }

        $sql = "INSERT INTO `".$this->tableName."` (".implode(',', array_keys($values)).")
            VALUES (" . implode(',', $fields) . ")";

        return $this->_db->query($sql, array_values($values));
    }

    /**
     * @param array $values
     * @param array $where
     * @param string $condition
     * @return CL_Query
     */
    public function update($values, $where = array(), $condition = 'AND')
    {
        $this->action = self::ACTION_UPDATE;
        $whereExpr = '';

        foreach($values as $field=>$value) {
            $fields[] = "`$field`=?";
        }

        foreach($where as $field=>$value) {
            $whereExpr[] = "`$field`=?";
        }

        $sql = "UPDATE `".$this->tableName."` SET ".implode(',', $fields);
        $sql .= $whereExpr ? " WHERE ".implode(' '.$condition, $whereExpr) : '';

        $values = array_merge(array_values($values), array_values($where));

        return $this->_db->query($sql, $values);
    }

    /**
     * @param $id
     * @param array $values
     * @return CL_Query
     */
    public function updateById($id, $values = array())
    {
        if(empty($values)) {
            return false;
        }

        foreach($values as $field=>$value) {
            $fields[] = "`$field`=?";
        }

        $sql = "UPDATE `".$this->tableName."` SET ".implode(',', $fields)." WHERE id = ?";
        $values = array_merge(array_values($values), array($id));

        return $this->_db->query($sql, $values);
    }

    /**
     * @return $this
     */
    public function save()
    {
        if(!$this->beforeSave()) {
            return false;
        }

        if(isset($this->{$this->_pk})) {
            $this->updateById($this->{$this->_pk}, $this->getAttributes());
        } else {
            $db = $this->insert($this->getAttributes());
            $this->setAttribute($this->_pk, $db->getLastId());
        }

        $this->afterSave();

        return $this;
    }

    /**
     * @param int $id
     * @return CL_Query
     */
    public function delete($id = null)
    {
        if(!$this->beforeDelete()) {
            return false;
        }

        $id = $id ? $id : $this->{$this->_pk};
        $sql = sprintf("DELETE FROM `%s` WHERE `%s` = ?", $this->tableName, $this->_pk);

        $this->_db->query($sql, array($id));

        $this->afterDelete();
    }

    /**
     * @param array $attributes
     * @return CL_Query
     */
    public function deleteByAttributes($attributes = array())
    {
        $where = array();
        $values = array_values($attributes);

        foreach($attributes as $attribute=>$value) {
            $where[] = "`$attribute` = ?";
        }

        $sql = "DELETE FROM `".$this->tableName."`";
        $sql .= $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return $this->_db->query($sql, $values);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($attribute, $value)
    {
        $this->_values[$attribute] = $value;
        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes($attributes = array())
    {
        foreach($attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetAttributes()
    {
        $this->_values = array();

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->_values;
    }

    /**
     * @return string
     */
    public function getPk()
    {
        return $this->_pk;
    }

    /**
     * @return array
     */
    public function relations()
    {
        return array();
    }

    /**
     *
     */
    public function setTableName()
    {
    }

    /**
     * @return bool
     */
    public function beforeSave() {
        return true;
    }

    /**
     *
     */
    public function afterSave() {

    }

    /**
     * @return bool
     */
    public function beforeDelete() {
        return true;
    }

    /**
     *
     */
    public function afterDelete() {

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