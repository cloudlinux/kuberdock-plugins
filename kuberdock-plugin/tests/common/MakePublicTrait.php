<?php


namespace tests;


trait MakePublicTrait
{
    /**
     * @var
     */
    protected $className;

    /**
     * @param $value
     */
    public function setTestedClass($value)
    {
        $this->className = $value;
    }

    /**
     * @param $name
     * @return \ReflectionMethod
     * @throws \Exception
     */
    protected function getMethod($name) {
        if (!$this->className) {
            throw new \Exception('Class not set');
        }

        $class = new \ReflectionClass($this->className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param $name
     * @return \ReflectionProperty
     * @throws \Exception
     */
    protected function getProperty($name) {
        if (!$this->className) {
            throw new \Exception('Class not set');
        }

        $class = new \ReflectionClass($this->className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property;
    }
}