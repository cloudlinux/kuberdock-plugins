<?php

define("CLIENTAREA", true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

global $CONFIG;

$base = \base\CL_Base::model();
$base->baseUrl = $CONFIG['SystemURL'];
$base->defaultController = 'KuberDock_Order';
$base->run();
