<?php


namespace components;


class Component
{
    /**
     * @var array
     */
    protected $values = array();
    /**
     * @var
     */
    protected static $models;

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->values[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        if (isset($this->values[$name])) {
            unset($this->values[$name]);
        }
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->values;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * @param string $values
     * @return $this
     */
    public function setAttributes($values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->values);
    }

    /**
     * @return self
     */
    public static function model()
    {
        $className = get_called_class();

        if (!isset(self::$models[$className])) {
            self::$models[$className] = new static();
        }
        return self::$models[$className];
    }
}