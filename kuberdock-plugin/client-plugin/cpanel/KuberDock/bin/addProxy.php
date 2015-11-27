#!/usr/bin/php5 -q

<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../init.php';

$opts = getopt('c:', array(
    'pod_name:',
    'rule:',
    'path:'
));

if(!isset($opts['pod_name']) || !isset($opts['rule']) || !isset($opts['path']) || !isset($opts['c'])) {
    echo <<<USAGE
Usage:
createProxy --pod_name="POD_NAME" --rule="HTACCESS_RULE" --path="HTACESS_PATH" -c TRY_COUNT

Params:
--pod_name          Pod name
--rule              Htaccess rule template
--path              Htaccess file path
-c                  Start count
USAGE;
    exit(0);
}

$podName = $opts['pod_name'];
$rule = $opts['rule'];
$path = $opts['path'];
$startCount = $opts['c'];
$userHome = getenv('HOME');

if(strpos($path, $userHome) !== true) {
    throw new CException('Wrong path');
}

$proxy = new Proxy();
$command = new KcliCommand('', '');
try {
    $pod = $command->describePod($podName);

    if(!isset($pod['podIP'])) {
        throw new CException(sprintf('Cannot get pod IP for pod %s', $podName));
    }
    $proxy->addRule($path, sprintf($rule, $pod['podIP']));
} catch(CException $e) {
    if($startCount >= 30) {
        echo $e->getMessage();
        exit;
    }

    $startCount++;
    $command = sprintf('at now +1min <<< \'%s --pod_name=%s --rule="%s" --path=%s -c %d\'',
        __FILE__, $podName, $rule, $path, $startCount);
    system($command);
}