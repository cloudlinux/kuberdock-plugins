<?php

include __DIR__ . '/init.php';

if (is_file(__DIR__ . '/dev-config.php')) {
    include __DIR__ . '/dev-config.php';
}

$testLoader = function($className) {
    $className = ltrim($className, '\\tests\\');

    $testDir = __DIR__ . DS . 'tests' . DS . 'classes' . DS;

    if ($pos = strrpos($className, '\\')) {
        $nameSpace = str_replace('\\', DS, substr($className, 0, $pos));
        $className = substr($className, $pos+1);

        $filePath = $testDir . $nameSpace . DS . $className . '.php';
    } else {
        $filePath = $testDir . $className . '.php';
    }

    if (file_exists($filePath)) {
        include_once $filePath;
    }
};

spl_autoload_register($testLoader, true, true);