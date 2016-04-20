#!/usr/bin/php5 -q

<?php

use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\components\Proxy;
use Kuberdock\classes\KcliCommand;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../init.php';

$opts = getopt('c:', array(
    'pod_name:',
    'rule:',
    'path:'
));

if(!isset($opts['pod_name']) || !isset($opts['rule']) || !isset($opts['path']) || !isset($opts['c'])) {
    echo <<<USAGE
Usage:
createProxy --pod_name="POD_NAME" --rule='HTACCESS_RULE' --path="HTACCESS_PATH" -c TRY_COUNT

Params:
--pod_name          Pod name
--rule              .htaccess rule template
--path              .htaccess file path
-c                  Start count
USAGE;
    exit(0);
}

$podName = $opts['pod_name'];
$rule = $opts['rule'];
$path = $opts['path'];
$startCount = $opts['c'];
$userHome = \Kuberdock\classes\Base::model()->getStaticPanel()->getHomeDir();

if(strpos($path, $userHome) === false) {
    throw new CException('Wrong path');
}

$proxy = new Proxy();
$command = new KcliCommand();
try {
    $pod = $command->describePod($podName);

    if(!isset($pod['podIP'])) {
        throw new CException(sprintf('Cannot get pod IP for pod %s', $podName));
    }

    $proxy->addRule($path, sprintf($rule, $pod['podIP']));
} catch(CException $e) {
    if($startCount >= 30) {
        echo $e->getMessage();
        exit(1);
    }

    $startCount++;
    $command = sprintf('at now +1min <<< \'%s --pod_name=%s --rule=\'%s\' --path=%s -c %d\'',
        __FILE__, $podName, $rule, $path, $startCount);
    system($command);
}