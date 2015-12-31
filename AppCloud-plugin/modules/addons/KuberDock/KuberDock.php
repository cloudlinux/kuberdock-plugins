<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */
require dirname(__FILE__) . '/../../servers/KuberDock/init.php';

function KuberDock_config()
{
    return array (
        'name' => 'KuberDock addon',
        'description' => '',
        'version' => '1.0',
        'author' => '<a href="http://www.cloudlinux.com" targer="_blank">CloudLinux</a>',
        'fields' => array(),
    );
}

function KuberDock_activate()
{
    try {
        KuberDock_Addon::model()->activate();

        return array(
            'status' =>  'success',
            'description' => 'Addon module activated',
        );
    } catch(Exception $e) {
        return array(
            'status' =>  'error',
            'description' => $e->getMessage(),
        );
    }
}

function KuberDock_deactivate()
{
    try {
        KuberDock_Addon::model()->deactivate();

        return array(
            'status' =>  'success',
            'description' => 'Addon module deactivated',
        );
    } catch(Exception $e) {
        return array(
            'status' =>  'error',
            'description' => $e->getMessage(),
        );
    }
}

function KuberDock_output($vars)
{
    $base = \base\CL_Base::model();
    $base->baseUrl = $vars['modulelink'];
    $base->defaultController = 'KuberDock_Addon';
    $base->run();
}