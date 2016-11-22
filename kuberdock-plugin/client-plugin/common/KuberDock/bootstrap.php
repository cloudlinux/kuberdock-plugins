<?php

include __DIR__ . '/init.php';

defined ('CURLOPT_URL') || define ('CURLOPT_URL', 10002);
defined ('CURLOPT_TIMEOUT') || define ('CURLOPT_TIMEOUT', 13);
define ('CURLOPT_CUSTOMREQUEST', 10036);
define ('CURLOPT_USERPWD', 10005);
define ('CURLOPT_RETURNTRANSFER', 19913);
define ('CURLOPT_SSL_VERIFYHOST', 81);
define ('CURLOPT_SSL_VERIFYPEER', 64);
define ('CURLOPT_FOLLOWLOCATION', 52);
define ('CURLOPT_POSTFIELDS', 10015);
define ('CURLOPT_HTTPHEADER', 10023);


$testLoader = function($className) {
    $className = preg_replace("/^(?:\\\\)?tests\\\\(?:Kuberdock\\\\)?/", '', $className);
    $testDir = __DIR__ . DS . '..' . DS . '..' . DS .  '..' . DS . 'tests' . DS . 'common' . DS;

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
