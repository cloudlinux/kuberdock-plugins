<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

class CL_Component {
    /**
     * @var array
     */
    protected $_values = array();
    /**
     * @var
     */
    protected static $_models;

    /**
     *
     */
    public function __construct()
    {
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
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = null)
    {
        if (is_null($className)) {
            $className = get_called_class();
        }

        if(!isset(self::$_models[$className])) {
            self::$_models[$className] = new $className;
        }

        return self::$_models[$className];
    }
} 