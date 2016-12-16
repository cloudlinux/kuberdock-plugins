<?php

namespace Kuberdock\classes;


class DI
{
    private static $instance;

    public static function get($name)
    {
        return self::container()->get($name);
    }

    public static function set($name, $value)
    {
        return self::container()->set($name, $value);
    }

    private function __construct()
    {
    }

    private static function container()
    {
        if (!isset(self::$instance)) {
            $containerBuilder = new \DI\ContainerBuilder;
            $containerBuilder->addDefinitions(__DIR__ . '/../config.php');
            self::$instance = $containerBuilder->build();
        }

        return self::$instance;
    }

    public function __clone()
    {
        throw new \Exception('Clone of DI is not allowed.');
    }

    public function __wakeup()
    {
        throw new \Exception('Unserializing of DI is not allowed.');
    }
}